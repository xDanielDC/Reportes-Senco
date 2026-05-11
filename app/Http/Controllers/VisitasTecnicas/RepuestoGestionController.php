<?php
namespace App\Http\Controllers\VisitasTecnicas;

use App\Http\Controllers\Controller;
use App\Models\Senco360\SolicitudParte;
use App\Models\Senco360\VisitaDetal;
use App\Models\Senco360\VisitaEstado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RepuestoGestionController extends Controller
{
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
        if ($user->hasRole('Asesor')) {
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

        if (!in_array($estadoActualId, $estadosGestionables, true)) {
            throw ValidationException::withMessages([
                'estado_id' => 'El estado actual del repuesto no es gestionable para tu rol.',
            ]);
        }

        $destinosValidos = $this->obtenerEstadosDestinoValidos($estadoActualId, $user);

        if (!in_array($estadoDestinoId, $destinosValidos, true)) {
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
            'ID_ESTADO'   => $estadoDestinoId,
            'OBSERVACION' => $observacion ?? $repuesto->OBSERVACION,
        ]);

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

        if (!$equipo) {
            return;
        }

        $equipo->tiposSolucion()->syncWithoutDetaching($soluciones->all());

        if (!$equipo->ID_SOLUCION) {
            $equipo->update([
                'ID_SOLUCION' => $soluciones->first(),
            ]);
        }
    }

    public function index(): Response
    {
        $user = auth()->user();
        $esAsesor    = $user->hasRole('Asesor');
        $esAsistente = $user->hasRole('AsistenteVentas');

        $query = SolicitudParte::with([
            'estado',
            'detalle.encabezado.rutaTecnica',
        ])->whereHas('detalle.encabezado', function ($q) {
            $q->where('DETALLE_PUBLICADO_COTIZACION', 1);
        });

        $this->aplicarAlcanceVisibilidad($query, $user);

        $repuestos = $query->orderBy('ID', 'desc')->get()->map(function($r) {
            // Información del repuesto desde vRepuestosMax
            $repuestoInfo = DB::connection('senco360')
                ->table('vRepuestosMax')
                ->where('Codigo Repuesto', $r->ID_COD_MAX_PARTES)
                ->first();

            // Información de la herramienta desde vHerramientasMax
            $herramientaInfo = DB::connection('senco360')
                ->table('vHerramientasMax')
                ->where('Cod Parte', $r->detalle?->ID_COD_MAX)
                ->first();

            return [
                'id'              => $r->ID,
                'codigo'          => $r->ID_COD_MAX_PARTES,
                'nombre_repuesto' => $repuestoInfo ? $repuestoInfo->{'Descripcion Repuesto'} : null,
                'proveedor'       => $repuestoInfo ? $repuestoInfo->{'Codigo Proveedor'} : null,
                'cantidad'        => $r->CANTIDAD,
                'observacion'     => $r->OBSERVACION,
                'estado'          => $r->estado?->ESTADO,
                'estado_id'       => $r->ID_ESTADO,
                'cliente'         => $r->detalle?->encabezado?->rutaTecnica?->NombreCliente,
                'nit'             => $r->detalle?->encabezado?->rutaTecnica?->Nit,
                'direccion'       => $r->detalle?->encabezado?->rutaTecnica?->DireccionCompleta,
                'tecnico'         => $r->detalle?->encabezado?->rutaTecnica?->CodTecnico,
                'visita_id'       => $r->detalle?->encabezado?->ID,
                'equipo'          => $r->detalle?->ID_COD_MAX,
                'nombre_equipo'   => $herramientaInfo ? $herramientaInfo->{'Descripcion Parte'} : null,
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
            'repuestos'          => $repuestos,
            'estados_disponibles'=> $estadosDisponibles,
            'transiciones_estado'=> $this->obtenerTransicionesPorRol($user),
            'es_asesor'          => $esAsesor,
            'es_asistente'       => $esAsistente,
        ]);
    }

    public function actualizarEstado(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $request->validate([
            'estado_id'                 => 'required|integer|in:13,14,15,16,17,18,19,27',
            'observacion'               => 'nullable|string|max:255',
            'soluciones_adicionales'    => 'nullable|array',
            'soluciones_adicionales.*'  => 'integer|exists:senco360.RT_tipo_solucion,ID',
        ]);

        DB::connection('senco360')->transaction(function () use ($request, $id) {
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
        });

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

    public function actualizarEstadoMasivo(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'repuesto_ids'    => 'required|array|min:1',
            'repuesto_ids.*'  => 'integer|distinct',
            'estado_id'       => 'required|integer|in:13,14,15,16,17,18,19,27',
            'observacion'     => 'nullable|string|max:255',
        ]);

        DB::connection('senco360')->transaction(function () use ($request) {
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
        });

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
