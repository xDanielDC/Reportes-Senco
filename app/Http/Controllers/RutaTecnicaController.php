<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRutaTecnicaRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\RutaTecnica;

class RutaTecnicaController extends Controller
{
    /**
     * Constructor - Aplicar middleware de permisos
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:rutas-tecnicas.crear')->only(['create', 'store', 'buscarClientes', 'obtenerDirecciones']);
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        $technicalUsers = Auth::user()->technicalUsers()
            ->whereHas('roles', function ($query) {
                $query->whereRaw('LOWER(name) IN (?, ?)', ['tecnico', 'técnico']);
            })
            ->whereNotNull('codigo_vendedor')
            ->select(['id', 'name', 'codigo_vendedor'])
            ->orderBy('name')
            ->get();

        return Inertia::render('RutasTecnicas/Create', [
            'technicalUsers' => $technicalUsers,
        ]);
    }

    /**
     * Guardar nueva ruta técnica
     */
    public function store(StoreRutaTecnicaRequest $request)
    {
        DB::beginTransaction();

        try {
            $codVendedor = Auth::user()->codigo_vendedor;
            $numeroRuta = RutaTecnica::generarNumeroRuta();
            $allowedCodTecnicos = Auth::user()->technicalUsers()
                ->whereHas('roles', function ($query) {
                    $query->whereRaw('LOWER(name) IN (?, ?)', ['tecnico', 'técnico']);
                })
                ->whereNotNull('codigo_vendedor')
                ->pluck('codigo_vendedor')
                ->map(function ($codigo) {
                    return trim($codigo);
                })
                ->all();
            
            Log::info('Guardando ruta técnica', [
                'numero_ruta' => $numeroRuta,
                'vendedor' => $codVendedor,
                'visitas' => count($request->visitas)
            ]);

            foreach ($request->visitas as $visita) {
                $codTecnico = trim($visita['cod_tecnico'] ?? '');
                if (empty($codTecnico) || !in_array($codTecnico, $allowedCodTecnicos, true)) {
                    throw new \Exception('El técnico seleccionado no es válido para tu usuario.');
                }

                RutaTecnica::create([
                    'NumeroRuta' => $numeroRuta,
                    'FechaInicio' => $request->fecha_inicio,
                    'FechaFin' => $request->fecha_fin,
                    'Nit' => $visita['nit'],
                    'NombreCliente' => $visita['nombre_cliente'],
                    'DireccionCompleta' => $visita['direccion_completa'],
                    'FechaVisita' => $visita['fecha_visita'],
                    'NomContacto' => $visita['nom_contacto'] ?? null,
                    'TelContacto' => $visita['tel_contacto'] ?? null,
                    'CodVendedor' => $codVendedor,
                    'CodTecnico' => $codTecnico,
                    'Observaciones' => $visita['observaciones'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('rutas-tecnicas.create')
                ->with('success', "Ruta técnica #{$numeroRuta} creada exitosamente con " . count($request->visitas) . " visita(s)");

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al crear ruta técnica', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Error al crear la ruta técnica: ' . $e->getMessage());
        }
    }

    /**
     * API: Buscar clientes desde la vista
     */
    public function buscarClientes(Request $request)
    {
        $search = trim($request->input('q', ''));

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $codigoVendedor = Auth::user()->codigo_vendedor;

        try {
            $clientes = DB::connection('sqlsrv')
                ->table('V_360_tec_clientes')
                ->select([
                    DB::raw('[Nit Cliente] AS ClienteId'),
                    DB::raw('[Nit Cliente] AS Nit'),
                    DB::raw('[Nombre Cliente] AS NombreCliente'),
                    DB::raw('[Cod Asesor] AS CodAsesor'),
                    DB::raw('[Nombre Asesor] AS NombreAsesor'),
                    DB::raw('ISNULL(Zona, \'\') AS Zona')
                ])
                ->where(DB::raw('RTRIM([Cod Asesor])'), '=', trim($codigoVendedor))
                ->where(function ($query) use ($search) {
                    $query->where(DB::raw('[Nit Cliente]'), 'LIKE', "%{$search}%")
                          ->orWhere(DB::raw('[Nombre Cliente]'), 'LIKE', "%{$search}%");
                })
                ->orderBy(DB::raw('[Nombre Cliente]'))
                ->limit(10)
                ->get();

            return response()->json($clientes);

        } catch (\Exception $e) {
            Log::error('Error al buscar clientes', [
                'search' => $search,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Error al buscar clientes',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Obtener direcciones de un cliente
     */
    public function obtenerDirecciones($clienteId)
    {
        try {
            $direcciones = DB::connection('sqlsrv')
                ->table('V_360_tec_clientes_sedes')
                ->select([
                    DB::raw("CONCAT(RTRIM([Nit Cliente]), '-', RTRIM(Sede)) AS DireccionId"),
                    DB::raw('RTRIM([Nit Cliente]) AS NitCliente'),
                    DB::raw('RTRIM(Sede) AS Sede'),
                    DB::raw('RTRIM([Direccion Sede]) AS DireccionCompleta'),
                    DB::raw('RTRIM(ISNULL([Complemento Direccion], \'\')) AS ComplementoDireccion'),
                    DB::raw('RTRIM(Ciudad) AS Ciudad'),
                    DB::raw('RTRIM(Departamento) AS Departamento'),
                    DB::raw('RTRIM(ISNULL([Nombre Contacto], \'\')) AS NombreContacto')
                ])
                ->where(DB::raw('[Nit Cliente]'), trim($clienteId))
                ->get();

            $direcciones = $direcciones->map(function($dir) {
                $direccionCompleta = $dir->DireccionCompleta;
                if (!empty($dir->ComplementoDireccion)) {
                    $direccionCompleta .= ' - ' . $dir->ComplementoDireccion;
                }
                
                return [
                    'DireccionId' => $dir->DireccionId,
                    'NitCliente' => $dir->NitCliente,
                    'Sede' => $dir->Sede,
                    'DireccionCompleta' => $direccionCompleta,
                    'Ciudad' => $dir->Ciudad,
                    'Departamento' => $dir->Departamento,
                    'NombreContacto' => $dir->NombreContacto
                ];
            });

            return response()->json($direcciones);

        } catch (\Exception $e) {
            Log::error('Error al obtener direcciones', [
                'nit' => $clienteId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Error al obtener direcciones',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
