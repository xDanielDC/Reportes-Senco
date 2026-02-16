<?php

namespace App\Http\Controllers;

use App\Services\ListaPreciosService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ListaPrecio; 

class ListaPreciosController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        protected ListaPreciosService $service
    ) {}

    /**
     * Mostrar la vista principal de lista de precios
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        // Sanitizar parámetros de entrada
        $params = $this->service->sanitizarParametros($request->all());

        // Obtener productos paginados
        $productos = $this->service->getProductos($params);

        // Obtener datos para filtros
        $filtrosData = $this->service->getFiltrosData();

        return Inertia::render('ListaPrecios/Index', [
            'productos' => $productos,
            'tipos' => $filtrosData['tipos'],
            'clases' => $filtrosData['clases'],
            'grupos' => $filtrosData['grupos'],
            'estadisticas' => $filtrosData['estadisticas'],
            'filters' => [
                'search' => $params['search'],
                'tipo' => $params['tipo'],
                'clase' => $params['clase'],
                'grupo' => $params['grupo'],
                'linea' => $params['linea'], // Alias de tipo
                'solo_con_stock' => $params['solo_con_stock'],
                'order_by' => $params['order_by'],
                'order_direction' => $params['order_direction'],
                'per_page' => $params['per_page'],
            ],
        ]);
    }

    /**
     * API endpoint para búsqueda (AJAX)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $params = $this->service->sanitizarParametros($request->all());
        $productos = $this->service->getProductos($params);

        return response()->json($productos);
    }

    /**
     * Obtener detalles de un producto específico
     *
     * @param string $codigo
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $codigo)
    {
        $producto = $this->service->getProductoDetalle($codigo);

        if (!$producto) {
            return response()->json([
                'message' => 'Producto no encontrado'
            ], 404);
        }

        return response()->json($producto);
    }

    /**
     * Exportar lista de precios a CSV
     *
     * @param Request $request
     * @return StreamedResponse
     */
    public function export(Request $request): StreamedResponse
    {
        $params = $this->service->sanitizarParametros($request->all());
        
        $filters = [
            'search' => $params['search'],
            'tipo' => $params['tipo'],
            'clase' => $params['clase'],
            'grupo' => $params['grupo'],
            'linea' => $params['linea'],
            'solo_con_stock' => $params['solo_con_stock'],
            'order_by' => $params['order_by'],
            'order_direction' => $params['order_direction'],
        ];

        // Usar exportación completa si se solicita
        $exportType = $request->get('export_type', 'csv');
        $csvContent = $exportType === 'excel' 
            ? $this->service->exportarExcel($filters)
            : $this->service->exportarCSV($filters);

        $response = new StreamedResponse(function() use ($csvContent) {
            echo $csvContent;
        });

        $filename = 'lista_precios_senco_' . date('Y-m-d_His') . '.csv';

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");

        return $response;
    }

    /**
     * Obtener datos para filtros (API)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function filtros()
    {
        $data = $this->service->getFiltrosData();
        return response()->json($data);
    }

    /**
     * Obtener productos relacionados
     *
     * @param string $codigo
     * @return \Illuminate\Http\JsonResponse
     */
    public function relacionados(string $codigo)
    {
        $relacionados = $this->service->getProductosRelacionados($codigo);

        return response()->json([
            'productos' => $relacionados,
            'total' => $relacionados->count(),
        ]);
    }

    /**
     * Obtener estadísticas
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function estadisticas()
    {
        $filtrosData = $this->service->getFiltrosData();
        
        return response()->json([
            'estadisticas' => $filtrosData['estadisticas'],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Obtener filtros dinámicos (clases y grupos según tipo/clase)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function filtrosDinamicos(Request $request)
    {
        $tipo = $request->get('tipo');
        $clase = $request->get('clase');

        return response()->json([
            'clases' => $this->service->getClasesByTipo($tipo),
            'grupos' => $this->service->getGruposByTipoClase($tipo, $clase),
        ]);
    }

    /**
     * Exportar lista de precios a PDF
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportPdf(Request $request)
{
    $query = ListaPrecio::query();

    if ($request->search) {
        $query->where('Descripcion', 'like', "%{$request->search}%");
    }

    if ($request->tipo) {
        $query->where('Tipo', $request->tipo);
    }

    if ($request->clase) {
        $query->where('Clase', $request->clase);
    }

    if ($request->grupo) {
        $query->where('Grupo', $request->grupo);
    }

    if ($request->solo_con_stock) {
        $query->where('Inventario', '>', 0);
    }

    $productos = $query->get();

    $pdf = Pdf::loadView('pdf.lista-precios', compact('productos'))
        ->setPaper('a4', 'landscape');

    return $pdf->stream('lista-precios.pdf');
}
}
