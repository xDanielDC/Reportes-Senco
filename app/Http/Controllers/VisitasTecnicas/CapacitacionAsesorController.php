<?php

namespace App\Http\Controllers\VisitasTecnicas;

use App\Http\Controllers\Controller;
use App\Models\RutaTecnica;
use App\Models\Senco360\TipoServicio;
use App\Models\Senco360\VisitaDetal;
use App\Models\Senco360\VisitaEncab;
use App\Models\Senco360\VisitaEstado;
use App\Models\Senco360\VisitaEstadoHistorico;
use App\Models\Senco360\VisitaFoto;
use App\Repositories\VisitasTecnicas\VisitaRepository;
use App\Services\VisitasTecnicas\InformeTecnicoVisitaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;

class CapacitacionAsesorController extends Controller
{
    private const ESTADO_PENDIENTE = 0;
    private const ESTADO_EN_PROCESO = 1;
    private const ESTADO_COMPLETADO = 2;

    public function __construct(
        protected VisitaRepository $repo,
        protected InformeTecnicoVisitaService $informeTecnicoService
    ) {
        $this->middleware('permission:rutas-tecnicas.crear');
    }

    /**
     * Renderizar formulario para registrar una capacitación libre.
     */
    public function createLibre()
    {
        $codigoAsesor = $this->codigoAsesor();
        abort_if(!$codigoAsesor, 403, 'Tu usuario no tiene un código de asesor asignado.');

        $tipoCapacitacion = TipoServicio::whereRaw("LOWER(TIPO_SERVICIO) LIKE '%capacit%'")
            ->orderBy('TIPO_SERVICIO')
            ->first();

        abort_if(!$tipoCapacitacion, 500, 'No se encontró un tipo de servicio de capacitación configurado.');

        return Inertia::render('VisitasTecnicas/Capacitaciones/CreateLibre', [
            'tipo_capacitacion_id' => $tipoCapacitacion->ID,
        ]);
    }

    /**
     * Guardar una capacitación libre: crea ruta técnica mínima, visita encab,
     * detalle, firma, fotos, historial e informe. Finaliza de una vez.
     */
    public function storeLibre(Request $request)
    {
        $codigoAsesor = $this->codigoAsesor();
        abort_if(!$codigoAsesor, 403, 'Tu usuario no tiene un código de asesor asignado.');

        $tipoCapacitacion = TipoServicio::whereRaw("LOWER(TIPO_SERVICIO) LIKE '%capacit%'")
            ->orderBy('TIPO_SERVICIO')
            ->first();

        abort_if(!$tipoCapacitacion, 500, 'No se encontró un tipo de servicio de capacitación configurado.');

        $request->validate([
            'nit'              => 'required|string|max:50',
            'nombre_cliente'   => 'required|string|max:255',
            'direccion'        => 'required|string|max:500',
            'nom_contacto'     => 'nullable|string|max:255',
            'tel_contacto'     => 'nullable|string|max:50',
            'fecha_visita'     => 'required|date',
            'correo_cliente'   => 'nullable|email',
            'titulo'           => 'required|string|max:200',
            'temas'            => 'nullable|string|max:1000',
            'observaciones'    => 'nullable|string|max:1000',
            'fecha_inicio'     => 'required|date',
            'fecha_fin'        => 'required|date|after_or_equal:fecha_inicio',
            'hora_inicio'      => 'required|date_format:H:i',
            'hora_fin'         => 'required|date_format:H:i',
            'firma'            => 'nullable|string',
            'fotos'            => 'nullable|array',
            'fotos.*'          => 'nullable|image|max:5120',
        ]);

        DB::beginTransaction();

        try {
            $user = auth()->user();
            $fechaHoy = now()->format('Y-m-d');
            $numeroRuta = RutaTecnica::generarNumeroRuta();

            // 1. Crear ruta técnica mínima
            $ruta = RutaTecnica::create([
                'NumeroRuta'        => $numeroRuta,
                'FechaInicio'       => $fechaHoy,
                'FechaFin'          => $fechaHoy,
                'Nit'               => $request->nit,
                'NombreCliente'     => $request->nombre_cliente,
                'DireccionCompleta' => $request->direccion,
                'FechaVisita'       => $request->fecha_visita,
                'NomContacto'       => $request->nom_contacto,
                'TelContacto'       => $request->tel_contacto,
                'CodVendedor'       => $codigoAsesor,
                'CodTecnico'        => $codigoAsesor,
                'Observaciones'     => null,
                'cerrada'           => 1,
            ]);

            // 2. Crear visita encab en estado Completado
            $visita = $this->repo->crear([
                'ID_VISITA'        => $ruta->IdVisita,
                'CORREO'           => $request->correo_cliente,
                'HORA_INICIO'      => $request->hora_inicio,
                'HORA_FIN'         => $request->hora_fin,
                'FECHA_INICIO'     => $request->fecha_inicio,
                'FECHA_FIN'        => $request->fecha_fin,
                'ID_TIPO_SERVICIO' => $tipoCapacitacion->ID,
                'OBSERVACIONES'    => null,
                'ID_ESTADO_ACTUAL' => self::ESTADO_COMPLETADO,
            ]);

            // 3. Crear detalle de capacitación
            $detal = VisitaDetal::create([
                'ID_ENC_VISITA'     => $visita->ID,
                'ID_COD_MAX'        => 'CAP',
                'TITULO'            => $request->titulo,
                'DESCRIPCION_FALLA' => $request->temas,
                'ID_SOLUCION'       => null,
                'OBSERVACIONES'     => $request->observaciones,
            ]);

            // 4. Guardar firma
            if ($request->filled('firma')) {
                $firmaBase64 = $request->firma;
                if (str_contains($firmaBase64, ',')) {
                    $firmaBase64 = explode(',', $firmaBase64)[1];
                }
                $firmaBlob = base64_decode($firmaBase64);
                $rutaFirma = 'visitas/firmas/' . $visita->ID . '_firma.png';
                Storage::disk('public')->put($rutaFirma, $firmaBlob);
                VisitaFoto::create([
                    'ID_DETALLE_VISITA' => $detal->ID,
                    'TIPO'              => 'FIRMA',
                    'RUTA_FOTO'         => $rutaFirma,
                ]);
            }

            // 5. Guardar fotos
            if ($request->hasFile('fotos')) {
                foreach ($request->file('fotos') as $foto) {
                    $rutaFoto = $foto->store('visitas/equipos/' . $detal->ID, 'public');
                    VisitaFoto::create([
                        'ID_DETALLE_VISITA' => $detal->ID,
                        'TIPO'              => 'DESPUES',
                        'RUTA_FOTO'         => $rutaFoto,
                    ]);
                }
            }

            // 6. Historial
            VisitaEstadoHistorico::create([
                'ID_ENC_VISITA' => $visita->ID,
                'ID_ESTADO'     => self::ESTADO_COMPLETADO,
                'FECHA'         => now(),
                'OBSERVACIONES' => $request->observaciones ?? 'Capacitación libre finalizada por asesor',
                'ID_USUARIO'    => $user->id,
            ]);

            // 7. Enviar informe
            $visitaFinalizada = $this->repo->findById($visita->ID, '');
            if ($visitaFinalizada) {
                $destinatarios = collect([
                    $visitaFinalizada->CORREO,
                    $user->email,
                    $this->informeTecnicoService::CORREO_FIJO_INFORME,
                ])
                    ->filter(fn ($email) => filled($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
                    ->map(fn ($email) => strtolower(trim($email)))
                    ->unique()
                    ->values()
                    ->all();

                if (!empty($destinatarios)) {
                    $pdfContenido = $this->informeTecnicoService->generarPdf($visitaFinalizada)->output();
                    $nombreArchivo = $this->informeTecnicoService->nombreArchivo($visitaFinalizada);

                    foreach ($destinatarios as $email) {
                        \Illuminate\Support\Facades\Mail::to($email)->send(
                            new \App\Mail\InformeTecnicoVisitaMail($visitaFinalizada, $pdfContenido, $nombreArchivo)
                        );
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => 'Capacitación registrada y finalizada correctamente.',
                'redirect' => route('visitastecnicas.capacitaciones.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar capacitación libre', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener el código del asesor (código_vendedor del usuario autenticado)
     */
    private function codigoAsesor(): ?string
    {
        $codigo = trim((string) (auth()->user()->codigo_vendedor ?? ''));
        return $codigo !== '' ? $codigo : null;
    }

    /**
     * Index: Listar todas las capacitaciones del asesor
     */
    public function index()
    {
        $user = auth()->user();
        $codigoAsesor = $this->codigoAsesor();

        abort_if(!$codigoAsesor, 403, 'Tu usuario no tiene un código de asesor asignado.');

        // Obtener rutas donde CodTecnico = codigo del asesor (capacitaciones del asesor)
        $capacitaciones = RutaTecnica::with('visitaEncab')
            ->where('CodTecnico', $codigoAsesor)
            ->orderBy('FechaVisita', 'desc')
            ->paginate(15);

        return Inertia::render('VisitasTecnicas/Visitas/Index', [
            'visitas' => $capacitaciones,
            'esCapacitacionModulo' => true,
            'puedeCrearCapacitacionLibre' => $user->permission_names->contains('capacitaciones.crear'),
            'titulo' => 'Mis Capacitaciones',
        ]);
    }

    /**
     * Show: Ver detalles de una capacitación específica
     */
    public function show(int $id)
    {
        $visita = $this->obtenerCapacitacionOAbortar($id);

        return Inertia::render('VisitasTecnicas/Visitas/Show', [
            'visita' => $visita,
            'es_capacitacion' => true,
        ]);
    }

    /**
     * Edit: Formulario para editar una capacitación
     */
    public function edit(string $id_visita)
    {
        $codigoAsesor = $this->codigoAsesor();
        abort_if(!$codigoAsesor, 403, 'Tu usuario no tiene un código de asesor asignado.');

        $estadoReprogramadoId = $this->obtenerEstadoId('Reprogramado');
        $ruta = RutaTecnica::with('visitaEncab')
            ->where('IdVisita', $id_visita)
            ->where('CodTecnico', $codigoAsesor)
            ->firstOrFail();

        $visitaExistente = $ruta->visitaEncab;
        abort_if(
            $visitaExistente && (int) $visitaExistente->ID_ESTADO_ACTUAL !== $estadoReprogramadoId,
            403,
            'Esta capacitación ya fue iniciada.'
        );

        return Inertia::render('VisitasTecnicas/Visitas/Create', [
            'id_visita' => $id_visita,
            'ruta_tecnica' => [
                'numero_ruta' => $ruta->NumeroRuta,
                'cliente' => $ruta->NombreCliente,
                'nit' => $ruta->Nit,
                'direccion' => $ruta->DireccionCompleta,
                'fecha_visita' => $ruta->FechaVisita ? Carbon::parse($ruta->FechaVisita)->format('Y-m-d') : null,
                'nom_contacto' => $ruta->NomContacto,
                'tel_contacto' => $ruta->TelContacto,
            ],
            'tipos_servicio' => [],
            'es_capacitacion' => true,
        ]);
    }

    /**
     * Update: Actualizar datos de capacitación (JSON)
     */
    public function update(Request $request, string $id_visita)
    {
        $codigoAsesor = $this->codigoAsesor();
        abort_if(!$codigoAsesor, 403, 'Tu usuario no tiene un código de asesor asignado.');

        $visita = $this->obtenerCapacitacionOAbortar($id_visita);
        abort_if((int) $visita->ID_ESTADO_ACTUAL !== self::ESTADO_EN_PROCESO, 403, 'Solo puedes editar capacitaciones en progreso.');

        $request->validate([
            'titulo' => 'nullable|string|max:200',
            'temas' => 'nullable|string|max:1000',
            'observaciones' => 'nullable|string|max:1000',
            'hora_inicio' => 'nullable|date_format:H:i',
            'hora_fin' => 'nullable|date_format:H:i',
        ]);

        try {
            $visita->update($request->only(['titulo', 'temas', 'observaciones', 'hora_inicio', 'hora_fin']));

            return response()->json(['success' => true, 'message' => 'Capacitación actualizada.']);
        } catch (\Exception $e) {
            Log::error('Error al actualizar capacitación', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Finalizar: Cambiar estado a COMPLETADO y enviar informe
     */
    public function finalizar(Request $request, string $id_visita)
    {
        $codigoAsesor = $this->codigoAsesor();
        abort_if(!$codigoAsesor, 403, 'Tu usuario no tiene un código de asesor asignado.');

        $visita = $this->obtenerCapacitacionOAbortar($id_visita);
        abort_if((int) $visita->ID_ESTADO_ACTUAL !== self::ESTADO_EN_PROCESO, 403, 'Solo puedes finalizar capacitaciones en progreso.');

        DB::beginTransaction();

        try {
            // Cambiar estado a COMPLETADO
            $visita->ID_ESTADO_ACTUAL = self::ESTADO_COMPLETADO;
            $visita->save();

            // Registrar en historial
            VisitaEstadoHistorico::create([
                'ID_VISITA' => $visita->ID_VISITA,
                'ID_ESTADO' => self::ESTADO_COMPLETADO,
                'ID_USUARIO' => auth()->id(),
                'FECHA_CAMBIO' => now(),
                'OBSERVACIONES' => $request->input('observaciones_cierre', 'Capacitación finalizada por asesor'),
            ]);

            // Generar PDF y enviar email
            $destinatarios = collect([$visita->CORREO ?? null, auth()->user()->email])
                ->filter(fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
                ->unique()
                ->values()
                ->toArray();

            if (!empty($destinatarios)) {
                $pdfContenido = $this->informeTecnicoService->generarPdf($visita)->output();
                $nombreArchivo = $this->informeTecnicoService->nombreArchivo($visita);

                foreach ($destinatarios as $email) {
                    \Illuminate\Support\Facades\Mail::to($email)->send(
                        new \App\Mail\InformeTecnicoVisitaMail($visita, $pdfContenido, $nombreArchivo)
                    );
                }
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Capacitación finalizada y informe enviado.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al finalizar capacitación', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete: Eliminar capacitación en estado PENDIENTE
     */
    public function destroy(string $id_visita)
    {
        $codigoAsesor = $this->codigoAsesor();
        abort_if(!$codigoAsesor, 403, 'Tu usuario no tiene un código de asesor asignado.');

        $ruta = RutaTecnica::with('visitaEncab')
            ->where('IdVisita', $id_visita)
            ->where('CodTecnico', $codigoAsesor)
            ->firstOrFail();

        $visita = $ruta->visitaEncab;
        abort_if(!$visita || (int) $visita->ID_ESTADO_ACTUAL !== self::ESTADO_PENDIENTE, 403, 'Solo puedes eliminar capacitaciones en estado pendiente.');

        DB::beginTransaction();

        try {
            $visita->delete();
            $ruta->delete();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Capacitación eliminada.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar capacitación', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener capacitación del asesor o abortar
     */
    private function obtenerCapacitacionOAbortar(string $idVisita): VisitaEncab
    {
        $codigoAsesor = $this->codigoAsesor();
        abort_if(!$codigoAsesor, 403, 'Tu usuario no tiene un código de asesor asignado.');

        $visita = VisitaEncab::where('ID_VISITA', $idVisita)
            ->whereHas('ruta', function ($query) use ($codigoAsesor) {
                $query->where('CodTecnico', $codigoAsesor);
            })
            ->firstOrFail();

        return $visita;
    }

    /**
     * Obtener ID de estado por nombre
     */
    private function obtenerEstadoId(string $nombre): int
    {
        $estado = VisitaEstado::where('ESTADO', 'like', "%{$nombre}%")->first();
        return $estado ? $estado->ID : 0;
    }
}
