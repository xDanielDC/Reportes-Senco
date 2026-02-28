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
        $this->middleware('permission:rutas-tecnicas.crear')->only(['create', 'store', 'buscarClientes', 'obtenerDirecciones']);
        $this->middleware('permission:rutas-tecnicas.ver')->only(['index', 'show']);
        $this->middleware('permission:rutas-tecnicas.editar')->only(['edit', 'update']);
    }

    /**
     * Formatear fecha con día de la semana en español
     * Formato: 2026-02-02 Lun
     */
    private function formatearFecha($fecha)
    {
        if (empty($fecha)) {
            return null;
        }
        
        if (is_string($fecha)) {
            $fecha = \Carbon\Carbon::parse($fecha);
        }
        
        // Días en español
        $dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        $diaSemana = $dias[$fecha->dayOfWeek];
        
        return $fecha->format('Y-m-d') . ' ' . $diaSemana;
    }

    /**
     * Mostrar índice de rutas técnicas del usuario actual
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $codVendedor = $user->codigo_vendedor;
        
        // Verificar permisos usando el attribute permission_names
        $permissionNames = $user->permission_names ?? collect([]);
        $permisos = [
            'crear' => $permissionNames->contains('rutas-tecnicas.crear'),
            'editar' => $permissionNames->contains('rutas-tecnicas.editar'),
            'eliminar' => $permissionNames->contains('rutas-tecnicas.eliminar'),
            'ver' => $permissionNames->contains('rutas-tecnicas.ver')
        ];
        
        // Determinar si el usuario puede ver todas las rutas o solo las propias
        // Los supervisores/administradores pueden ver todas las rutas
        $roleNames = $user->getRoleNames() ?? collect([]);
        $verTodasLasRutas = $permissionNames->contains('rutas-tecnicas.ver-todos') || 
                            $roleNames->contains('super-admin') ||
                            $roleNames->contains('AsistenteVentas') ||
                            $roleNames->contains('Gerencia') ||
                            $roleNames->contains('supervisor');
        
        // Validar que el usuario tenga código de vendedor si no es supervisor
        if (!$verTodasLasRutas && !$codVendedor) {
            return Inertia::render('RutasTecnicas/index', [
                'rutas' => [],
                'filtros' => $request->only(['fecha_inicio', 'fecha_fin']),
                'permisos' => $permisos,
                'error' => 'Tu usuario no tiene un código de vendedor asignado. Contacta al administrador.'
            ]);
        }

        // Auto-cerrar rutas vencidas (viernes después de las 2pm)
        try {
            \App\Models\RutaTecnica::cerrarRutasVencidas();
        } catch (\Exception $e) {
            // Ignorar errores al cerrar rutas automáticamente
        }

        // Construir consulta base - obtener todas las visitas sin paginar para agrupar
        $query = RutaTecnica::query()
            ->when(!$verTodasLasRutas, function ($q) use ($user, $codVendedor) {
                // Obtener técnicos que comparten con este usuario
                $codigosTecnicos = [];
                try {
                    $codigosTecnicos = $user->technicalUsers()
                        ->whereHas('roles', function ($roleQuery) {
                            $roleQuery->whereRaw('LOWER(name) IN (?, ?)', ['tecnico', 'técnico']);
                        })
                        ->whereNotNull('codigo_vendedor')
                        ->pluck('codigo_vendedor')
                        ->map(fn($codigo) => trim($codigo))
                        ->all();
                } catch (\Exception $e) {
                    // Si hay error, es array vacío
                }
                
                // Mostrar rutas propias O rutas de técnicos compartidos
                return $q->where(function ($subQuery) use ($codVendedor, $codigosTecnicos) {
                    $subQuery->where('CodVendedor', trim($codVendedor));
                    
                    if (!empty($codigosTecnicos)) {
                        $subQuery->orWhereIn('CodTecnico', $codigosTecnicos);
                    }
                });
            })
            ->orderBy('FechaInicio', 'desc')
            ->orderBy('NumeroRuta', 'desc')
            ->orderBy('FechaVisita', 'asc');

        // Aplicar filtros si existen
        if ($request->filled('fecha_inicio')) {
            $query->where('FechaInicio', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->where('FechaInicio', '<=', $request->fecha_fin);
        }

        // Obtener todas las visitas y agrupar por NumeroRuta
        $visitasData = $query->get();
        
        // Agrupar por NumeroRuta
        $rutasAgrupadas = $visitasData->groupBy('NumeroRuta')->map(function ($grupo, $numeroRuta) {
            $primeraVisita = $grupo->first();
            $ultimaVisita = $grupo->last();
            
            // Formatear fechas si son objetos Carbon
            $fechaInicio = $primeraVisita->FechaInicio;
            if (is_string($fechaInicio)) {
                $fechaInicio = \Carbon\Carbon::parse($fechaInicio);
            }
            $fechaFin = $ultimaVisita->FechaFin;
            if (is_string($fechaFin)) {
                $fechaFin = \Carbon\Carbon::parse($fechaFin);
            }
            
            return [
                'NumeroRuta' => $numeroRuta,
                'FechaInicio' => $this->formatearFecha($fechaInicio),
                'FechaFin' => $this->formatearFecha($fechaFin),
                'CodVendedor' => $primeraVisita->CodVendedor,
                'cerrada' => $primeraVisita->cerrada ?? false,
                'totalVisitas' => $grupo->count(),
                'visitas' => $grupo->values()->all(),
            ];
        })->values();

        return Inertia::render('RutasTecnicas/index', [
            'rutas' => $rutasAgrupadas->values()->all(),
            'filtros' => $request->only(['fecha_inicio', 'fecha_fin']),
            'permisos' => $permisos
        ]);
    }

    /**
     * Mostrar detalles de una ruta técnica específica
     */
    public function show($numeroRuta)
{
    $user = Auth::user();
    $codVendedor = $user->codigo_vendedor;
    
    $permissionNames = $user->permission_names ?? collect([]);
    $roleNames = $user->getRoleNames() ?? collect([]);
    $verTodasLasRutas = $permissionNames->contains('rutas-tecnicas.ver-todos') || 
                        $roleNames->contains('super-admin') ||
                        $roleNames->contains('AsistenteVentas') ||
                        $roleNames->contains('Gerencia') ||
                        $roleNames->contains('admin') ||
                        $roleNames->contains('supervisor');

    // Verificar que la ruta existe
    $rutaExiste = RutaTecnica::where('NumeroRuta', $numeroRuta)->exists();
    if (!$rutaExiste) {
        return redirect()->route('rutas-tecnicas.index')
            ->with('error', 'Ruta técnica no encontrada.');
    }

    // Si no es admin, verificar que tiene acceso a esta ruta
    if (!$verTodasLasRutas) {
        $codigosTecnicos = [];
        try {
            $codigosTecnicos = $user->technicalUsers()
                ->whereHas('roles', function ($q) {
                    $q->whereRaw('LOWER(name) IN (?, ?)', ['tecnico', 'técnico']);
                })
                ->whereNotNull('codigo_vendedor')
                ->pluck('codigo_vendedor')
                ->map(fn($codigo) => trim($codigo))
                ->all();
        } catch (\Exception $e) {}

        // Tiene acceso si la ruta es propia O si comparte técnico
        $tieneAcceso = RutaTecnica::where('NumeroRuta', $numeroRuta)
            ->where(function ($q) use ($codVendedor, $codigosTecnicos) {
                $q->where('CodVendedor', trim($codVendedor));
                if (!empty($codigosTecnicos)) {
                    $q->orWhereIn('CodTecnico', $codigosTecnicos);
                }
            })
            ->exists();

        if (!$tieneAcceso) {
            return redirect()->route('rutas-tecnicas.index')
                ->with('error', 'No tienes acceso a esta ruta técnica.');
        }
    }

    // Obtener TODAS las visitas de la ruta sin filtrar por CodVendedor
    $visitas = RutaTecnica::where('NumeroRuta', $numeroRuta)
        ->orderBy('FechaVisita', 'asc')
        ->get()
        ->map(function ($visita) {
            $fechaVisita = $visita->FechaVisita;
            if (is_string($fechaVisita)) {
                $fechaVisita = \Carbon\Carbon::parse($fechaVisita);
            }
            return [
                'idVisita' => $visita->IdVisita,
                'NumeroRuta' => $visita->NumeroRuta,
                'Nit' => $visita->Nit,
                'NombreCliente' => $visita->NombreCliente,
                'DireccionCompleta' => $visita->DireccionCompleta,
                'FechaVisita' => $this->formatearFecha($fechaVisita),
                'NomContacto' => $visita->NomContacto,
                'TelContacto' => $visita->TelContacto,
                'CodVendedor' => $visita->CodVendedor,
                'CodTecnico' => $visita->CodTecnico,
                'Observaciones' => $visita->Observaciones,
            ];
        });

    $visitaRaw = RutaTecnica::where('NumeroRuta', $numeroRuta)
        ->orderBy('FechaInicio', 'desc')
        ->first();

    $fechaInicio = is_string($visitaRaw->FechaInicio) 
        ? \Carbon\Carbon::parse($visitaRaw->FechaInicio) 
        : $visitaRaw->FechaInicio;
    $fechaFin = is_string($visitaRaw->FechaFin) 
        ? \Carbon\Carbon::parse($visitaRaw->FechaFin) 
        : $visitaRaw->FechaFin;

    return Inertia::render('RutasTecnicas/Show', [
        'ruta' => [
            'NumeroRuta' => $numeroRuta,
            'FechaInicio' => $this->formatearFecha($fechaInicio),
            'FechaFin' => $this->formatearFecha($fechaFin),
            'CodVendedor' => $visitaRaw->CodVendedor,
            'cerrada' => $visitaRaw->cerrada ?? false,
            'visitas' => $visitas,
        ]
    ]);
}

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        $user = Auth::user();
        $codVendedor = $user->codigo_vendedor;
        
        // Verificar si hay rutas abiertas (propias o compartidas con técnicos)
        $rutaAbierta = null;
        if ($codVendedor) {
            // Obtener técnicos que comparten con este usuario
            $codigosTecnicos = [];
            try {
                $codigosTecnicos = $user->technicalUsers()
                    ->whereHas('roles', function ($query) {
                        $query->whereRaw('LOWER(name) IN (?, ?)', ['tecnico', 'técnico']);
                    })
                    ->whereNotNull('codigo_vendedor')
                    ->pluck('codigo_vendedor')
                    ->map(fn($codigo) => trim($codigo))
                    ->all();
            } catch (\Exception $e) {
                // Si hay error, es array vacío
            }
            
            // Buscar ruta abierta: propia o de técnico compartido
            $rutaAbierta = RutaTecnica::where('cerrada', false)
                ->where(function ($query) use ($codVendedor, $codigosTecnicos) {
                    $query->where('CodVendedor', trim($codVendedor));
                    
                    if (!empty($codigosTecnicos)) {
                        $query->orWhereIn('CodTecnico', $codigosTecnicos);
                    }
                })
                ->orderBy('FechaInicio', 'desc')
                ->first();
            
            // Verificar si la ruta existente debe cerrarse automáticamente
            if ($rutaAbierta && $rutaAbierta->debeCerrarse()) {
                $rutaAbierta->cerrar();
                $rutaAbierta = null;
            }
        }
        
        // Obtener técnicos disponibles
        $technicalUsers = [];
        try {
            $technicalUsers = $user->technicalUsers()
                ->whereHas('roles', function ($query) {
                    $query->whereRaw('LOWER(name) IN (?, ?)', ['tecnico', 'técnico']);
                })
                ->whereNotNull('codigo_vendedor')
                ->select(['id', 'name', 'codigo_vendedor'])
                ->orderBy('name')
                ->get();
        } catch (\Exception $e) {
            // Si el método no existe, continuar con array vacío
        }

        // Construir mensaje personalizado si la ruta es compartida
        $mensaje = null;
        if ($rutaAbierta) {
            $esPropia = $rutaAbierta->CodVendedor === trim($codVendedor);
            if ($esPropia) {
                $mensaje = 'Ya tienes una ruta abierta. Agrega las visitas allí.';
            } else {
                // Ruta de otro asesor que comparte técnico
                $mensaje = "El asesor {$rutaAbierta->CodVendedor} ya inició la ruta del {$rutaAbierta->FechaInicio} al {$rutaAbierta->FechaFin}. Agrega tus visitas allí.";
            }
        }

        $rutaAbiertaData = null;
        if ($rutaAbierta) {
            $nombreVendedor = $rutaAbierta->CodVendedor;
            try {
                $vendedor = \App\Models\User::where('codigo_vendedor', trim($rutaAbierta->CodVendedor))->first();
                if ($vendedor) {
                    $nombreVendedor = $vendedor->name;
                }
            } catch (\Exception $e) {}
        
            $rutaAbiertaData = [
                'NumeroRuta' => $rutaAbierta->NumeroRuta,
                'FechaInicio' => \Carbon\Carbon::parse($rutaAbierta->FechaInicio)->format('Y-m-d'),
                'FechaFin' => \Carbon\Carbon::parse($rutaAbierta->FechaFin)->format('Y-m-d'),
                'CodVendedor' => $rutaAbierta->CodVendedor,
                'NombreVendedor' => $nombreVendedor,
            ];
        }
        
        return Inertia::render('RutasTecnicas/Create', [
            'technicalUsers' => $technicalUsers,
            'rutaAbierta' => $rutaAbiertaData,
            'mensaje' => $mensaje,
        ]);
    }

    /**
     * Guardar nueva ruta técnica
     */
    public function store(StoreRutaTecnicaRequest $request)
    {
        DB::beginTransaction();

        try {
            $user = Auth::user();
            $codVendedor = $user->codigo_vendedor;
            
            // Obtener técnicos que comparten con este usuario
            $codigosTecnicos = [];
            try {
                $codigosTecnicos = $user->technicalUsers()
                    ->whereHas('roles', function ($query) {
                        $query->whereRaw('LOWER(name) IN (?, ?)', ['tecnico', 'técnico']);
                    })
                    ->whereNotNull('codigo_vendedor')
                    ->pluck('codigo_vendedor')
                    ->map(fn($codigo) => trim($codigo))
                    ->all();
            } catch (\Exception $e) {
                // Si hay error, es array vacío
            }
            
            // Verificar si ya existe una ruta abierta (propia o compartida) con rango de fechas que se superponga
            $rutaExistente = RutaTecnica::where('cerrada', false)
                ->where(function ($query) use ($codVendedor, $codigosTecnicos, $request) {
                    // Buscar ruta propia O de técnico compartido
                    $query->where(function ($q) use ($codVendedor) {
                        $q->where('CodVendedor', trim($codVendedor));
                    });
                    
                    if (!empty($codigosTecnicos)) {
                        $query->orWhereIn('CodTecnico', $codigosTecnicos);
                    }
                })
                ->where(function ($query) use ($request) {
                    // Que tenga superposición de fechas
                    $query->where(function ($q) use ($request) {
                        $q->where('FechaInicio', '<=', $request->fecha_inicio)
                          ->where('FechaFin', '>=', $request->fecha_inicio);
                    })->orWhere(function ($q) use ($request) {
                        $q->where('FechaInicio', '<=', $request->fecha_fin)
                          ->where('FechaFin', '>=', $request->fecha_fin);
                    })->orWhere(function ($q) use ($request) {
                        $q->where('FechaInicio', '>=', $request->fecha_inicio)
                          ->where('FechaFin', '<=', $request->fecha_fin);
                    });
                })
                ->first();
            
            // Si existe ruta abierta (propia o compartida), unirse a ella
            if ($rutaExistente) {
                DB::commit();
                
                return redirect()
                    ->route('rutas-tecnicas.edit', $rutaExistente->NumeroRuta)
                    ->with('success', 'Te uniste a la ruta técnica #' . $rutaExistente->NumeroRuta . '. Agrega tus visitas.');
            }
            
            // Generar número de ruta
            $numeroRuta = RutaTecnica::generarNumeroRuta();
            
            // Obtener lista de técnicos permitidos
            $allowedCodTecnicos = [];
            try {
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
            } catch (\Exception $e) {
                // Si el método no existe, permitir cualquier técnico
            }
            
            Log::info('Guardando ruta técnica', [
                'numero_ruta' => $numeroRuta,
                'vendedor' => $codVendedor,
                'visitas' => count($request->visitas)
            ]);

            foreach ($request->visitas as $visita) {
                $codTecnico = trim($visita['cod_tecnico'] ?? '');
                if (empty($codTecnico) || (count($allowedCodTecnicos) > 0 && !in_array($codTecnico, $allowedCodTecnicos, true))) {
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
                    'cerrada' => false,
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

    /**
     * Mostrar formulario de edición de una ruta técnica
     */
    public function edit($numeroRuta)
    {
        $user = Auth::user();
        $codVendedor = $user->codigo_vendedor;
        
        // Verificar permisos
        $permissionNames = $user->permission_names ?? collect([]);
        $roleNames = $user->getRoleNames() ?? collect([]);
        $verTodasLasRutas = $permissionNames->contains('rutas-tecnicas.ver-todos') || 
                            $permissionNames->contains('rutas-tecnicas.editar') ||
                            $roleNames->contains('super-admin') ||
                            $roleNames->contains('administrador') ||
                            $roleNames->contains('admin') ||
                            $roleNames->contains('supervisor');
        
        if (!$verTodasLasRutas && !$codVendedor) {
            return redirect()->route('rutas-tecnicas.index')
                ->with('error', 'Tu usuario no tiene un código de vendedor asignado.');
        }

        // Buscar la ruta
        $visitasQuery = RutaTecnica::where('NumeroRuta', $numeroRuta)
            ->when(!$verTodasLasRutas, function ($q) use ($codVendedor) {
                return $q->where('CodVendedor', trim($codVendedor));
            })
            ->orderBy('FechaInicio', 'desc')
            ->orderBy('FechaVisita', 'asc');
        
        $visitasCount = (clone $visitasQuery)->count();
        
        if ($visitasCount === 0) {
            return redirect()->route('rutas-tecnicas.index')
                ->with('error', 'Ruta técnica no encontrada.');
        }

        // Obtener las visitas
        $visitas = $visitasQuery->get()->map(function ($visita) use ($codVendedor) {
            $fechaVisita = $visita->FechaVisita;
            if (is_string($fechaVisita)) {
                $fechaVisita = \Carbon\Carbon::parse($fechaVisita);
            }
            
            return [
                'id' => $visita->IdVisita,
                'idVisita' => $visita->IdVisita,
                'cliente_id' => $visita->Nit,
                'nit' => $visita->Nit,
                'nombre_cliente' => $visita->NombreCliente,
                'direccion_id' => $visita->DireccionCompleta,
                'direccion_completa' => $visita->DireccionCompleta,
                'fecha_visita' => $this->formatearFecha($fechaVisita),
                'fecha_visita_raw' => $fechaVisita ? \Carbon\Carbon::parse($fechaVisita)->format('Y-m-d') : '',
                'nom_contacto' => $visita->NomContacto,
                'tel_contacto' => $visita->TelContacto,
                'cod_tecnico' => $visita->CodTecnico,
                'observaciones' => $visita->Observaciones,
                'cod_vendedor' => $visita->CodVendedor,
                'es_propia' => trim($visita->CodVendedor) === trim($codVendedor),
            ];
        });

        $primeraVisita = $visitas->first();
        $visitaRaw = RutaTecnica::where('NumeroRuta', $numeroRuta)
            ->when(!$verTodasLasRutas, function ($q) use ($codVendedor) {
                return $q->where('CodVendedor', trim($codVendedor));
            })
            ->orderBy('FechaInicio', 'desc')
            ->first();

        // Obtener técnicos disponibles
        $technicalUsers = [];
        try {
            $technicalUsers = $user->technicalUsers()
                ->whereHas('roles', function ($query) {
                    $query->whereRaw('LOWER(name) IN (?, ?)', ['tecnico', 'técnico']);
                })
                ->whereNotNull('codigo_vendedor')
                ->select(['id', 'name', 'codigo_vendedor'])
                ->orderBy('name')
                ->get();
        } catch (\Exception $e) {
            // Si el método no existe, continuar con array vacío
        }

        return Inertia::render('RutasTecnicas/Edit', [
            'numeroRuta' => $numeroRuta,
            'fecha_inicio' => $this->formatearFecha($visitaRaw->FechaInicio),
            'fecha_fin' => $this->formatearFecha($visitaRaw->FechaFin),
            'fecha_inicio_raw' => $visitaRaw->FechaInicio ? \Carbon\Carbon::parse($visitaRaw->FechaInicio)->format('Y-m-d') : '',
            'fecha_fin_raw' => $visitaRaw->FechaFin ? \Carbon\Carbon::parse($visitaRaw->FechaFin)->format('Y-m-d') : '',
            'visitas' => $visitas,
            'technicalUsers' => $technicalUsers,
        ]);
    }

    /**
     * Actualizar una ruta técnica (agregar/editar/eliminar visitas)
     */
    public function update(Request $request, $numeroRuta)
    {
        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            $codVendedor = $user->codigo_vendedor;
            
            // Verificar permisos
            $permissionNames = $user->permission_names ?? collect([]);
            $roleNames = $user->getRoleNames() ?? collect([]);
            $verTodasLasRutas = $permissionNames->contains('rutas-tecnicas.ver-todos') || 
                                $permissionNames->contains('rutas-tecnicas.editar') ||
                                $roleNames->contains('super-admin') ||
                                $roleNames->contains('administrador') ||
                                $roleNames->contains('admin') ||
                                $roleNames->contains('supervisor');
            
            if (!$verTodasLasRutas && !$codVendedor) {
                return back()->with('error', 'Tu usuario no tiene un código de vendedor asignado.');
            }

            // Verificar que la ruta existe y está abierta
            $rutaExistente = RutaTecnica::where('NumeroRuta', $numeroRuta)
                ->first();

            if (!$rutaExistente) {
                return back()->with('error', 'Ruta técnica no encontrada.');
            }

            if ($rutaExistente->cerrada) {
                return back()->with('error', 'No puedes modificar una ruta cerrada.');
            }
            
            // Obtener las visitas existentes de esta ruta
            $visitasExistentes = RutaTecnica::where('NumeroRuta', $numeroRuta)
                ->get()
                ->keyBy('IdVisita');
            
            // Obtener técnicos permitidos
            $allowedCodTecnicos = [];
            try {
                $allowedCodTecnicos = $user->technicalUsers()
                    ->whereHas('roles', function ($query) {
                        $query->whereRaw('LOWER(name) IN (?, ?)', ['tecnico', 'técnico']);
                    })
                    ->whereNotNull('codigo_vendedor')
                    ->pluck('codigo_vendedor')
                    ->map(fn($codigo) => trim($codigo))
                    ->all();
            } catch (\Exception $e) {
                // Si el método no existe, permitir cualquier técnico
            }
            
            // Obtener datos de visitas del request
            $visitasData = $request->input('visitas', []);
            $idsAGuardar = [];
            
            foreach ($visitasData as $visita) {
                $visitaId = $visita['idVisita'] ?? $visita['id'] ?? null;
                $codTecnico = trim($visita['cod_tecnico'] ?? '');
                
                // Validar técnico permitido
                if (empty($codTecnico) || (count($allowedCodTecnicos) > 0 && !in_array($codTecnico, $allowedCodTecnicos, true))) {
                    throw new \Exception('El técnico seleccionado no es válido para tu usuario.');
                }
                
                // Si tiene ID válido, es una visita existente - validar ownership
                if (!empty($visitaId) && $visitaId > 0) {
                    $visitaOriginal = $visitasExistentes[$visitaId] ?? null;
                    
                    if (!$visitaOriginal) {
                        throw new \Exception('Visita no encontrada.');
                    }
                    
                    // Solo el dueño de la visita o admin/supervisor pueden modificarla
                    if (!$verTodasLasRutas && trim($visitaOriginal->CodVendedor) !== trim($codVendedor)) {
                        throw new \Exception('Esta visita fue agregada por el asesor ' . $visitaOriginal->CodVendedor . '. Coordina directamente con él para modificarla.');
                    }
                    
                    $idsAGuardar[] = $visitaId;
                    
                    // Actualizar visita existente
                    $fechaInicio = $request->fecha_inicio;
                    $fechaFin = $request->fecha_fin;
                    $fechaVisita = $visita['fecha_visita'];
                    
                    // Extraer solo la fecha (sin día de la semana) si viene formateada
                    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $fechaInicio)) {
                        $fechaInicio = substr($fechaInicio, 0, 10);
                    }
                    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $fechaFin)) {
                        $fechaFin = substr($fechaFin, 0, 10);
                    }
                    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $fechaVisita)) {
                        $fechaVisita = substr($fechaVisita, 0, 10);
                    }
                    
                    RutaTecnica::where('IdVisita', $visitaId)->update([
                        'Nit' => $visita['nit'],
                        'NombreCliente' => $visita['nombre_cliente'],
                        'DireccionCompleta' => $visita['direccion_completa'],
                        'FechaVisita' => $fechaVisita,
                        'NomContacto' => $visita['nom_contacto'] ?? '',
                        'TelContacto' => $visita['tel_contacto'] ?? '',
                        'CodTecnico' => $codTecnico,
                        'Observaciones' => $visita['observaciones'] ?? '',
                    ]);
                } else {
                    // Nueva visita - crear con CodVendedor del usuario actual
                    $fechaInicio = $request->fecha_inicio;
                    $fechaFin = $request->fecha_fin;
                    $fechaVisita = $visita['fecha_visita'];
                    
                    // Extraer solo la fecha (sin día de la semana) si viene formateada
                    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $fechaInicio)) {
                        $fechaInicio = substr($fechaInicio, 0, 10);
                    }
                    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $fechaFin)) {
                        $fechaFin = substr($fechaFin, 0, 10);
                    }
                    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $fechaVisita)) {
                        $fechaVisita = substr($fechaVisita, 0, 10);
                    }
                    
                    RutaTecnica::create([
                        'NumeroRuta' => $numeroRuta,
                        'FechaInicio' => $fechaInicio,
                        'FechaFin' => $fechaFin,
                        'Nit' => $visita['nit'],
                        'NombreCliente' => $visita['nombre_cliente'],
                        'DireccionCompleta' => $visita['direccion_completa'],
                        'FechaVisita' => $fechaVisita,
                        'NomContacto' => $visita['nom_contacto'] ?? '',
                        'TelContacto' => $visita['tel_contacto'] ?? '',
                        'CodVendedor' => $codVendedor,
                        'CodTecnico' => $codTecnico,
                        'Observaciones' => $visita['observaciones'] ?? '',
                    ]);
                }
            }
            
            // Eliminar las visitas que ya no están en la lista (pero solo propias)
            foreach ($visitasExistentes as $visitaExistente) {
                if (!in_array($visitaExistente->IdVisita, $idsAGuardar)) {
                    // Solo el dueño puede eliminar
                    if (!$verTodasLasRutas && trim($visitaExistente->CodVendedor) !== trim($codVendedor)) {
                        continue; // No eliminar visitas de otros asesores
                    }
                    
                    $visitaExistente->delete();
                }
            }
            
            DB::commit();

            return redirect()->route('rutas-tecnicas.show', $numeroRuta)
                ->with('success', 'Ruta técnica actualizada correctamente.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al actualizar ruta técnica', [
                'numero_ruta' => $numeroRuta,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Error al actualizar la ruta técnica: ' . $e->getMessage());
        }
    }

    /**
     * API: Obtener sugerencias de contactos de una dirección específica
     * Retorna los últimos contactos registrados para esa dirección
     */
    public function obtenerDatosContactoRecomendados($clienteId, \Illuminate\Http\Request $request)
    {
        try {
            $direccion = $request->query('direccion');
            
            if (!$direccion) {
                return response()->json([
                    'sugerencias' => []
                ]);
            }
            
            // Buscar los últimos 10 registros de contactos para esta dirección específica
            $visitas = RutaTecnica::where('Nit', trim($clienteId))
                ->where('DireccionCompleta', 'like', '%' . trim($direccion) . '%')
                ->where(function($q) {
                    $q->whereNotNull('NomContacto')
                      ->orWhereNotNull('TelContacto');
                })
                ->orderBy('FechaVisita', 'desc')
                ->orderBy('IdVisita', 'desc')
                ->limit(10)
                ->get(['NomContacto', 'TelContacto', 'FechaVisita']);

            // Agrupar por combinación de nombre y teléfono para eliminar duplicados
            $sugereciasUnicas = [];
            $vistas = [];
            
            foreach ($visitas as $visita) {
                $key = md5($visita->NomContacto . $visita->TelContacto);
                
                if (!isset($vistas[$key])) {
                    $vistas[$key] = true;
                    $sugereciasUnicas[] = [
                        'nomContacto' => $visita->NomContacto,
                        'telContacto' => $visita->TelContacto,
                        'fechaVisita' => $visita->FechaVisita ? \Carbon\Carbon::parse($visita->FechaVisita)->format('Y-m-d') : null,
                        'label' => ($visita->NomContacto ? $visita->NomContacto : '') . 
                                  ($visita->TelContacto ? ' - ' . $visita->TelContacto : '') .
                                  ($visita->FechaVisita ? ' (' . \Carbon\Carbon::parse($visita->FechaVisita)->format('d/m/Y') . ')' : '')
                    ];
                }
            }

            return response()->json([
                'sugerencias' => $sugereciasUnicas
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener contactos recomendados', [
                'nit' => $clienteId,
                'direccion' => $request->query('direccion'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Error al obtener datos de contacto',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
