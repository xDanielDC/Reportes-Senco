<?php

namespace App\Http\Controllers\VisitasTecnicas;

use App\Http\Controllers\Controller;
use App\Mail\NotificacionRechazoCotizacion;
use App\Mail\NotificacionRepuestoCotizado;
use App\Mail\NotificacionRepuestoDespachado;
use App\Mail\NotificacionRepuestoEnEspera;
use App\Mail\NotificacionRepuestoSolicitado;
use App\Models\Senco360\SolicitudParte;
use App\Models\Senco360\VisitaDetal;
use App\Models\Senco360\VisitaEstado;
use App\Models\Senco360\VisitaEstadoHistorico;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RepuestoGestionController extends Controller
{
    private const ESTADOS_FLUJO_COTIZACION = [13, 14, 15, 16, 17, 18, 27];

    private const TRANSICIONES_ASESOR = [
        13 => [27, 14, 15],
        27 => [14, 15],
    ];

    private const TRANSICIONES_ASISTENTE = [
        14 => [16, 17, 18],
        16 => [17, 18],
        17 => [16, 18],
    ];

    private const TRANSICIONES_TECNICO = [
        18 => [19],
    ];

    public function __construct()
    {
        $this->middleware('permission:visitastecnicas.repuestos.ver|visitastecnicas.repuestos.ver-todos')
            ->only(['index', 'actualizarEstadoMasivo']);

        $this->middleware('permission:visitastecnicas.ver|visitastecnicas.ver-todos|visitastecnicas.repuestos.ver|visitastecnicas.repuestos.ver-todos')
            ->only(['actualizarEstado']);
    }

    private function puedeVerTodosLosRepuestos($user): bool
    {
        return $user->hasRole('super-admin')
            || $user->hasPermissionTo('visitastecnicas.repuestos.ver-todos');
    }

    private function aplicarAlcanceVisibilidad($query, $user)
    {
        if ($this->puedeVerTodosLosRepuestos($user)) {
            return $query;
        }

        return $query->whereHas('detalle.encabezado.rutaTecnica', function ($q) use ($user) {
            $q->where('CodVendedor', $user->codigo_vendedor);
        });
    }

    private function obtenerTransicionesPorRol($user): array
    {
        if ($user->hasAnyRole(['Asesor', 'AsesorPruebas'])) {
            return self::TRANSICIONES_ASESOR;
        }

        if ($user->hasRole('AsistenteVentas')) {
            return self::TRANSICIONES_ASISTENTE;
        }

        if ($user->hasAnyRole(['Tecnico', 'Técnico'])) {
            return self::TRANSICIONES_TECNICO;
        }

        return [];
    }

    private function obtenerIdsEstadosPorRol($user): array
    {
        return collect($this->obtenerTransicionesPorRol($user))
            ->flatMap(fn (array $destinos, int $origen) => array_merge([$origen], $destinos))
            ->unique()
            ->values()
            ->all();
    }

    private function obtenerEstadosGestionablesPorRol($user): array
    {
        return array_map('intval', array_keys($this->obtenerTransicionesPorRol($user)));
    }

    private function obtenerEstadosDestinoValidos(int $estadoActualId, $user): array
    {
        return collect($this->obtenerTransicionesPorRol($user)[$estadoActualId] ?? [])
            ->map(fn ($estadoId) => (int) $estadoId)
            ->values()
            ->all();
    }

    private function validarAccesoRepuesto(SolicitudParte $repuesto, $user): void
    {
        if ($this->puedeVerTodosLosRepuestos($user)) {
            return;
        }

        if ($user->hasAnyRole(['Tecnico', 'Técnico'])) {
            abort_if(
                trim((string) $repuesto->detalle?->encabezado?->rutaTecnica?->CodTecnico) !== trim((string) $user->codigo_vendedor),
                403,
                'No tienes acceso a este repuesto.'
            );

            return;
        }

        abort_if(
            trim((string) $repuesto->detalle?->encabezado?->rutaTecnica?->CodVendedor) !== trim((string) $user->codigo_vendedor),
            403,
            'No tienes acceso a este repuesto.'
        );
    }

    private function validarCambioEstado(SolicitudParte $repuesto, int $estadoDestinoId, $user): void
    {
        $estadoActualId = (int) $repuesto->ID_ESTADO;
        $estadosGestionables = $this->obtenerEstadosGestionablesPorRol($user);

        if (! in_array($estadoActualId, $estadosGestionables, true)) {
            throw ValidationException::withMessages([
                'estado_id' => 'El estado actual del repuesto no es gestionable para tu rol.',
            ]);
        }

        $destinosValidos = $this->obtenerEstadosDestinoValidos($estadoActualId, $user);

        if (! in_array($estadoDestinoId, $destinosValidos, true)) {
            throw ValidationException::withMessages([
                'estado_id' => 'La transición de estado solicitada no es válida.',
            ]);
        }
    }

    private function aplicarCambioEstado(
        SolicitudParte $repuesto,
        int $estadoDestinoId,
        ?string $observacion,
        array $solucionesAdicionales = []
    ): void {
        $repuesto->update([
            'ID_ESTADO' => $estadoDestinoId,
        ]);

        $detalle = VisitaDetal::find($repuesto->ID_DETALLE_VISITA);
        $idEncVisita = $detalle?->ID_ENC_VISITA;

        if ($idEncVisita) {
            VisitaEstadoHistorico::create([
                'ID_ENC_VISITA' => $idEncVisita,
                'ID_ESTADO' => $estadoDestinoId,
                'FECHA' => now(),
                'OBSERVACIONES' => filled($observacion) ? $observacion : null,
                'ID_USUARIO' => auth()->id(),
                'ID_SOLICITUD_PARTE' => $repuesto->ID,
            ]);
        }

        if ($estadoDestinoId !== 19) {
            return;
        }

        $soluciones = collect($solucionesAdicionales)
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        if ($soluciones->isEmpty()) {
            return;
        }

        $equipo = VisitaDetal::find($repuesto->ID_DETALLE_VISITA);

        if (! $equipo) {
            return;
        }

        $equipo->tiposSolucion()->syncWithoutDetaching($soluciones->all());

        if (! $equipo->ID_SOLUCION) {
            $equipo->update([
                'ID_SOLUCION' => $soluciones->first(),
            ]);
        }
    }

    private function enviarNotificacionRepuestoSolicitado(int $estadoDestinoId, Collection $repuestos, ?string $observacionCambio = null): void
    {
        if ($estadoDestinoId !== 14 || $repuestos->isEmpty()) {
            return;
        }

        $visitaEncabezado = $repuestos->first()->detalle?->encabezado;
        if (! $visitaEncabezado) {
            return;
        }

        $codigoVendedor = $visitaEncabezado->rutaTecnica?->CodVendedor;
        $asesor = null;
        if ($codigoVendedor) {
            $asesor = User::whereRaw('LTRIM(RTRIM(codigo_vendedor)) = ?', [$codigoVendedor])->first();
        }

        $url = route('visitastecnicas.repuestos.index', [
            'visita_id' => (int) $visitaEncabezado->ID,
        ]);

        $observacionSolicitado = filled($observacionCambio) ? $observacionCambio : null;
        $repuestosParaEmail = collect($this->formatearRepuestosParaEmail($repuestos))
            ->map(fn (array $repuesto) => array_merge($repuesto, ['observacion' => $observacionSolicitado]))
            ->values()
            ->all();

        $asistentes = User::role('AsistenteVentas')
            ->whereNotNull('email')
            ->where('email', '<>', '')
            ->get();

        $tecnico = $this->obtenerTecnicoAsignado($visitaEncabezado);

        $destinatarios = collect([$tecnico, ...$asistentes->all()])
            ->filter(fn ($usuario) => filled($usuario?->email))
            ->unique(fn ($usuario) => strtolower(trim($usuario->email)))
            ->values();

        if ($destinatarios->isEmpty()) {
            return;
        }

        foreach ($destinatarios as $destinatario) {
            Mail::to($destinatario->email)
                ->send(new NotificacionRepuestoSolicitado(
                    $repuestosParaEmail,
                    $url,
                    auth()->user()?->name,
                    $visitaEncabezado,
                    $asesor,
                    null,
                    $tecnico
                ));
        }
    }

    private function formatearRepuestosParaEmail(Collection $repuestos): array
    {
        $codigos = $repuestos
            ->pluck('ID_COD_MAX_PARTES')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $repuestosInfo = DB::connection('senco360')
            ->table('vRepuestosMax')
            ->select([
                'Codigo Repuesto as codigo',
                'Descripcion Repuesto as descripcion',
                'Codigo Proveedor as codigo_proveedor',
            ])
            ->whereIn('Codigo Repuesto', $codigos)
            ->get()
            ->keyBy(fn ($row) => trim((string) $row->codigo));

        return $repuestos->map(function (SolicitudParte $repuesto) use ($repuestosInfo) {
            $info = $repuestosInfo->get(trim((string) $repuesto->ID_COD_MAX_PARTES));

            return [
                'codigo_max' => $repuesto->ID_COD_MAX_PARTES,
                'codigo_comodidad' => $info?->codigo_proveedor,
                'nombre' => $info?->descripcion,
                'cantidad' => $repuesto->CANTIDAD,
                'observacion' => $repuesto->OBSERVACION,
            ];
        })->values()->all();
    }

    private function obtenerTecnicoAsignado($visitaEncabezado): ?User
    {
        $codigoTecnico = $visitaEncabezado->rutaTecnica?->CodTecnico;

        if (! $codigoTecnico) {
            return null;
        }

        return User::whereRaw('LTRIM(RTRIM(codigo_vendedor)) = ?', [$codigoTecnico])
            ->orWhere('cedula', $codigoTecnico)
            ->orWhere('username', $codigoTecnico)
            ->first();
    }

    private function obtenerAsesorVisita($visitaEncabezado): ?User
    {
        $codigoVendedor = $visitaEncabezado->rutaTecnica?->CodVendedor;

        if (! $codigoVendedor) {
            return null;
        }

        return User::whereRaw('LTRIM(RTRIM(codigo_vendedor)) = ?', [$codigoVendedor])->first();
    }

    private function enviarNotificacionRechazoCotizacion(Collection $repuestos, ?string $observacionCambio = null): void
    {
        if ($repuestos->isEmpty()) {
            Log::info('No hay repuestos para notificar rechazo cotización');

            return;
        }

        $visitaEncabezado = $repuestos->first()->detalle?->encabezado;
        if (! $visitaEncabezado) {
            Log::info('No se encontró encabezado de visita para notificación rechazo cotización');

            return;
        }

        $codigoTecnico = $visitaEncabezado->rutaTecnica?->CodTecnico;
        Log::info('Código técnico obtenido: '.$codigoTecnico);
        $tecnico = $this->obtenerTecnicoAsignado($visitaEncabezado);
        Log::info('Técnico encontrado: '.($tecnico ? $tecnico->name.' - '.$tecnico->email : 'No encontrado'));

        if (! $tecnico || ! $tecnico->email) {
            Log::info('Técnico no encontrado o sin email para notificación rechazo cotización');

            return;
        }

        $asesor = $this->obtenerAsesorVisita($visitaEncabezado);

        $url = route('visitastecnicas.visitas.show', [
            'id' => (int) $visitaEncabezado->ID,
        ]);

        $observacionRechazo = filled($observacionCambio) ? $observacionCambio : null;
        $repuestosParaEmail = collect($this->formatearRepuestosParaEmail($repuestos))
            ->map(fn (array $repuesto) => array_merge($repuesto, ['observacion' => $observacionRechazo]))
            ->values()
            ->all();

        Log::info('Enviando notificación de rechazo cotización a: '.$tecnico->email);
        Mail::to($tecnico->email)
            ->send(new NotificacionRechazoCotizacion(
                $visitaEncabezado,
                $tecnico,
                $repuestosParaEmail,
                $url,
                $asesor
            ));
        Log::info('Notificación de rechazo cotización enviada exitosamente');
    }

    private function enviarNotificacionRepuestoEnEspera(Collection $repuestos, ?string $observacionCambio = null): void
    {
        if ($repuestos->isEmpty()) {
            Log::info('No hay repuestos para notificar repuesto en espera');

            return;
        }

        $visitaEncabezado = $repuestos->first()->detalle?->encabezado;
        if (! $visitaEncabezado) {
            Log::info('No se encontró encabezado de visita para notificación repuesto en espera');

            return;
        }

        $codigoVendedor = $visitaEncabezado->rutaTecnica?->CodVendedor;
        $asesor = null;
        if ($codigoVendedor) {
            $asesor = User::whereRaw('LTRIM(RTRIM(codigo_vendedor)) = ?', [$codigoVendedor])->first();
        }

        $tecnico = $this->obtenerTecnicoAsignado($visitaEncabezado);

        $url = route('visitastecnicas.visitas.show', [
            'id' => (int) $visitaEncabezado->ID,
        ]);

        $observacionEspera = filled($observacionCambio) ? $observacionCambio : null;
        $repuestosParaEmail = collect($this->formatearRepuestosParaEmail($repuestos))
            ->map(fn (array $repuesto) => array_merge($repuesto, ['observacion' => $observacionEspera]))
            ->values()
            ->all();

        $asesorLocal = $this->obtenerAsesorVisita($visitaEncabezado);

        $destinatarios = collect([$tecnico, $asesorLocal])
            ->filter(fn ($usuario) => filled($usuario?->email))
            ->unique(fn ($usuario) => strtolower(trim($usuario->email)))
            ->values();

        if ($destinatarios->isEmpty()) {
            Log::info('Técnico y asesor no encontrados o sin email para notificación repuesto en espera');

            return;
        }

        foreach ($destinatarios as $destinatario) {
            Log::info('Enviando notificación de repuesto en espera a: '.$destinatario->email);
            Mail::to($destinatario->email)
                ->send(new NotificacionRepuestoEnEspera(
                    $visitaEncabezado,
                    $destinatario,
                    $repuestosParaEmail,
                    $url,
                    $asesorLocal
                ));
        }
        Log::info('Notificación de repuesto en espera enviada exitosamente');
    }

    private function enviarNotificacionRepuestoCotizado(Collection $repuestos, ?string $observacionCambio = null): void
    {
        if ($repuestos->isEmpty()) {
            Log::info('No hay repuestos para notificar repuesto cotizado');

            return;
        }

        $visitaEncabezado = $repuestos->first()->detalle?->encabezado;
        if (! $visitaEncabezado) {
            Log::info('No se encontró encabezado de visita para notificación repuesto cotizado');

            return;
        }

        $tecnico = $this->obtenerTecnicoAsignado($visitaEncabezado);
        if (! $tecnico || ! $tecnico->email) {
            Log::info('Técnico no encontrado o sin email para notificación repuesto cotizado');

            return;
        }

        $url = route('visitastecnicas.visitas.show', [
            'id' => (int) $visitaEncabezado->ID,
        ]);

        $observacionCotizado = filled($observacionCambio) ? $observacionCambio : null;
        $repuestosParaEmail = collect($this->formatearRepuestosParaEmail($repuestos))
            ->map(fn (array $repuesto) => array_merge($repuesto, ['observacion' => $observacionCotizado]))
            ->values()
            ->all();

        Log::info('Enviando notificación de repuesto cotizado a: '.$tecnico->email);
        Mail::to($tecnico->email)
            ->send(new NotificacionRepuestoCotizado(
                $visitaEncabezado,
                $tecnico,
                $repuestosParaEmail,
                $url
            ));
        Log::info('Notificación de repuesto cotizado enviada exitosamente');
    }

    private function enviarNotificacionRepuestoDespachado(Collection $repuestos, ?string $observacionCambio = null): void
    {
        if ($repuestos->isEmpty()) {
            Log::info('No hay repuestos para notificar repuesto despachado');

            return;
        }

        $visitaEncabezado = $repuestos->first()->detalle?->encabezado;
        if (! $visitaEncabezado) {
            Log::info('No se encontró encabezado de visita para notificación repuesto despachado');

            return;
        }

        $asesor = $this->obtenerAsesorVisita($visitaEncabezado);
        $tecnico = $this->obtenerTecnicoAsignado($visitaEncabezado);

        $destinatarios = collect([$asesor, $tecnico])
            ->filter(fn ($usuario) => filled($usuario?->email))
            ->unique(fn ($usuario) => strtolower(trim($usuario->email)))
            ->values();

        if ($destinatarios->isEmpty()) {
            Log::info('Asesor y técnico no encontrados o sin email para notificación repuesto despachado');

            return;
        }

        $url = route('visitastecnicas.visitas.show', [
            'id' => (int) $visitaEncabezado->ID,
        ]);

        $observacionDespacho = filled($observacionCambio) ? $observacionCambio : null;
        $repuestosParaEmail = collect($this->formatearRepuestosParaEmail($repuestos))
            ->map(fn (array $repuesto) => array_merge($repuesto, ['observacion' => $observacionDespacho]))
            ->values()
            ->all();

        foreach ($destinatarios as $destinatario) {
            Log::info('Enviando notificación de repuesto despachado a: '.$destinatario->email);
            Mail::to($destinatario->email)
                ->send(new NotificacionRepuestoDespachado(
                    $visitaEncabezado,
                    $destinatario,
                    $repuestosParaEmail,
                    $url,
                    $asesor,
                    $tecnico
                ));
        }

        Log::info('Notificación de repuesto despachado enviada exitosamente');
    }

    private function enviarNotificacionesCambioEstado(int $estadoDestinoId, Collection $repuestos, ?string $observacionCambio = null): void
    {
        if ($repuestos->isEmpty()) {
            return;
        }

        match ($estadoDestinoId) {
            14 => $this->enviarNotificacionRepuestoSolicitado(14, $repuestos, $observacionCambio),
            15 => $this->enviarNotificacionRechazoCotizacion($repuestos, $observacionCambio),
            17 => $this->enviarNotificacionRepuestoEnEspera($repuestos, $observacionCambio),
            18 => $this->enviarNotificacionRepuestoDespachado($repuestos, $observacionCambio),
            27 => $this->enviarNotificacionRepuestoCotizado($repuestos, $observacionCambio),
            default => null,
        };
    }

    public function index(): Response
    {
        $user = auth()->user();
        $esAsesor = $user->hasAnyRole(['Asesor', 'AsesorPruebas']);
        $esAsistente = $user->hasRole('AsistenteVentas');
        $estadosGestionables = $this->obtenerEstadosGestionablesPorRol($user);

        $query = SolicitudParte::with([
            'estado',
            'detalle.encabezado.rutaTecnica',
        ])->whereHas('detalle.encabezado', function ($q) {
            $q->where('DETALLE_PUBLICADO_COTIZACION', 1);
        });

        $this->aplicarAlcanceVisibilidad($query, $user);

        if (! empty($estadosGestionables)) {
            $query->whereIn('ID_ESTADO', $estadosGestionables);
        }

        $solicitudes = $query->orderBy('ID', 'desc')->get();

        $codigosRepuestos = $solicitudes
            ->pluck('ID_COD_MAX_PARTES')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $codigosHerramientas = $solicitudes
            ->map(fn (SolicitudParte $repuesto) => $repuesto->detalle?->ID_COD_MAX)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $repuestosInfo = empty($codigosRepuestos)
            ? collect()
            : DB::connection('senco360')
                ->table('vRepuestosMax')
                ->select([
                    'Codigo Repuesto as codigo',
                    'Descripcion Repuesto as descripcion',
                    'Codigo Proveedor as codigo_proveedor',
                ])
                ->whereIn('Codigo Repuesto', $codigosRepuestos)
                ->get()
                ->keyBy(fn ($row) => trim((string) $row->codigo));

        $herramientasInfo = empty($codigosHerramientas)
            ? collect()
            : DB::connection('senco360')
                ->table('vHerramientasMax')
                ->select([
                    'Cod Parte as codigo',
                    'Descripcion Parte as descripcion',
                    'Codigo Proveedor as codigo_proveedor',
                ])
                ->whereIn('Cod Parte', $codigosHerramientas)
                ->get()
                ->keyBy(fn ($row) => trim((string) $row->codigo));

        $repuestos = $solicitudes->map(function (SolicitudParte $r) use ($repuestosInfo, $herramientasInfo) {
            $repuestoInfo = $repuestosInfo->get(trim((string) $r->ID_COD_MAX_PARTES));
            $herramientaInfo = $herramientasInfo->get(trim((string) ($r->detalle?->ID_COD_MAX ?? '')));

            return [
                'id' => $r->ID,
                'codigo' => $r->ID_COD_MAX_PARTES,
                'nombre_repuesto' => $repuestoInfo?->descripcion,
                'proveedor' => $repuestoInfo?->codigo_proveedor,
                'es_urgente' => (bool) $r->ES_URGENTE,
                'cantidad' => $r->CANTIDAD,
                'observacion' => $r->OBSERVACION,
                'estado' => $r->estado?->ESTADO,
                'estado_id' => $r->ID_ESTADO,
                'cliente' => $r->detalle?->encabezado?->rutaTecnica?->NombreCliente,
                'nit' => $r->detalle?->encabezado?->rutaTecnica?->Nit,
                'direccion' => $r->detalle?->encabezado?->rutaTecnica?->DireccionCompleta,
                'tecnico' => $r->detalle?->encabezado?->rutaTecnica?->CodTecnico,
                'visita_id' => $r->detalle?->encabezado?->ID,
                'equipo' => $r->detalle?->ID_COD_MAX,
                'nombre_equipo' => $herramientaInfo ? $herramientaInfo->descripcion : null,
            ];
        });

        // Estados disponibles según rol, respetando el orden funcional por ID
        $idsEstados = $this->obtenerIdsEstadosPorRol($user);
        $estadosDisponibles = empty($idsEstados)
            ? collect()
            : VisitaEstado::whereIn('ID', $idsEstados)
                ->orderBy('ID')
                ->get(['ID', 'ESTADO']);

        return Inertia::render('VisitasTecnicas/Repuestos/Index', [
            'repuestos' => $repuestos,
            'estados_disponibles' => $estadosDisponibles,
            'transiciones_estado' => $this->obtenerTransicionesPorRol($user),
            'es_asesor' => $esAsesor,
            'es_asistente' => $esAsistente,
        ]);
    }

    public function actualizarEstado(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $request->validate([
            'estado_id' => 'required|integer|in:13,14,15,16,17,18,19,27',
            'observacion' => 'nullable|string|max:255',
            'soluciones_adicionales' => 'nullable|array',
            'soluciones_adicionales.*' => 'integer|exists:senco360.RT_tipo_solucion,ID',
        ]);

        $repuestoParaNotificar = null;

        DB::connection('senco360')->transaction(function () use ($request, $id, &$repuestoParaNotificar) {
            $repuesto = SolicitudParte::with('detalle.encabezado.rutaTecnica')->findOrFail($id);
            $user = auth()->user();

            $this->validarAccesoRepuesto($repuesto, $user);
            $this->validarCambioEstado($repuesto, (int) $request->estado_id, $user);
            $this->aplicarCambioEstado(
                $repuesto,
                (int) $request->estado_id,
                $request->observacion,
                $request->input('soluciones_adicionales', [])
            );

            $repuestoParaNotificar = $repuesto;
        });

        if ($repuestoParaNotificar) {
            $this->enviarNotificacionesCambioEstado(
                (int) $request->estado_id,
                collect([$repuestoParaNotificar]),
                $request->observacion
            );
        }

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Estado actualizado correctamente.');
        }

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente.',
            ]);
        }

        return back()->with('success', 'Estado actualizado correctamente.');
    }

    public function obtenerObservacionesRepuesto(int $id): JsonResponse
    {
        $repuesto = SolicitudParte::with('detalle.encabezado')->findOrFail($id);
        $user = auth()->user();
        $this->validarAccesoRepuesto($repuesto, $user);

        $detalle = $repuesto->detalle;
        $idEncVisita = $detalle?->ID_ENC_VISITA;

        $observaciones = collect();

        if (filled($repuesto->OBSERVACION)) {
            $observaciones->push((object) [
                'fecha' => null,
                'observacion' => $repuesto->OBSERVACION,
                'origen' => 'Repuesto',
                'estado' => $repuesto->estado?->ESTADO,
                'estado_id' => (int) $repuesto->ID_ESTADO,
            ]);
        }

        if ($idEncVisita) {
            $historicos = VisitaEstadoHistorico::query()
                ->where('ID_ENC_VISITA', $idEncVisita)
                ->where('ID_SOLICITUD_PARTE', $id)
                ->whereNotNull('OBSERVACIONES')
                ->where('OBSERVACIONES', '<>', '')
                ->whereIn('ID_ESTADO', [13, 14, 15, 16, 17, 18, 19, 27])
                ->orderBy('FECHA', 'asc')
                ->with(['estado', 'usuario:id,name'])
                ->get(['ID_ESTADO', 'FECHA', 'OBSERVACIONES', 'ID_USUARIO'])
                ->map(function ($h) {
                    return (object) [
                        'fecha' => $h->FECHA,
                        'observacion' => $h->OBSERVACIONES,
                        'origen' => 'Histórico',
                        'estado' => $h->estado?->ESTADO,
                        'estado_id' => (int) $h->ID_ESTADO,
                        'usuario' => $h->usuario?->name,
                    ];
                })
                ->values();

            $observaciones = $observaciones->merge($historicos);
        }

        return response()->json([
            'observaciones' => $observaciones,
        ]);
    }

    public function actualizarEstadoMasivo(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'repuesto_ids' => 'required|array|min:1',
            'repuesto_ids.*' => 'integer|distinct',
            'estado_id' => 'required|integer|in:13,14,15,16,17,18,19,27',
            'observacion' => 'nullable|string|max:255',
        ]);

        $repuestosParaNotificar = collect();

        DB::connection('senco360')->transaction(function () use ($request, &$repuestosParaNotificar) {
            $user = auth()->user();
            $repuestoIds = collect($request->input('repuesto_ids', []))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $repuestos = SolicitudParte::with('detalle.encabezado.rutaTecnica')
                ->whereIn('ID', $repuestoIds)
                ->get();

            if ($repuestos->count() !== $repuestoIds->count()) {
                throw ValidationException::withMessages([
                    'repuesto_ids' => 'Uno o varios repuestos ya no están disponibles para gestionar.',
                ]);
            }

            $estadosActuales = $repuestos
                ->pluck('ID_ESTADO')
                ->map(fn ($estadoId) => (int) $estadoId)
                ->unique()
                ->values();

            if ($estadosActuales->count() !== 1) {
                throw ValidationException::withMessages([
                    'repuesto_ids' => 'Todos los repuestos seleccionados deben compartir el mismo estado actual.',
                ]);
            }

            foreach ($repuestos as $repuesto) {
                $this->validarAccesoRepuesto($repuesto, $user);
                $this->validarCambioEstado($repuesto, (int) $request->estado_id, $user);
            }

            foreach ($repuestos as $repuesto) {
                $this->aplicarCambioEstado(
                    $repuesto,
                    (int) $request->estado_id,
                    $request->observacion
                );
            }

            $repuestosParaNotificar = $repuestos;
        });

        $this->enviarNotificacionesCambioEstado(
            (int) $request->estado_id,
            $repuestosParaNotificar,
            $request->observacion
        );

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Estados actualizados correctamente.');
        }

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Estados actualizados correctamente.',
            ]);
        }

        return back()->with('success', 'Estados actualizados correctamente.');
    }
}
