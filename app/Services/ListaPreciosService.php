<?php

namespace App\Services;

use App\Repositories\ListaPreciosRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ListaPreciosService
{
    /**
     * Constructor
     */
    public function __construct(
        protected ListaPreciosRepository $repository
    ) {}

    /**
     * Obtener productos con paginación
     *
     * @param array $request Datos del request
     * @return LengthAwarePaginator
     */
    public function getProductos(array $request): LengthAwarePaginator
    {
        $filters = [
            'search' => $request['search'] ?? null,
            'tipo' => $request['tipo'] ?? null,
            'clase' => $request['clase'] ?? null,
            'grupo' => $request['grupo'] ?? null,
            'linea' => $request['linea'] ?? null,
            'solo_con_stock' => $request['solo_con_stock'] ?? false,
            'order_by' => $request['order_by'] ?? 'Tipo',
            'order_direction' => $request['order_direction'] ?? 'asc',
        ];

        $perPage = $request['per_page'] ?? 15;

        return $this->repository->getPaginated($filters, $perPage);
    }

    /**
     * Exportar productos a CSV
     *
     * @param array $filters
     * @return string CSV content
     */
    public function exportarCSV(array $filters = []): string
    {
        $productos = $this->repository->search($filters);

        // Encabezados del CSV
        $csv = "\xEF\xBB\xBF"; // BOM UTF-8
        $csv .= "Tipo,Clase,Grupo,Subgrupo,Código,Referencia,Descripción,Precio,Precio Mínimo,ML/Caja,CJ/CRTN,Inventario\n";

        foreach ($productos as $producto) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $this->escapeCsv($producto->Tipo),
                $this->escapeCsv($producto->Clase),
                $this->escapeCsv($producto->Grupo),
                $this->escapeCsv($producto->Subgrupo),
                $this->escapeCsv($producto->{'Cod Max'}),
                $this->escapeCsv($producto->Referencia),
                $this->escapeCsv($producto->Descripcion),
                $producto->Precio ?? 0,
                $producto->Minimo ?? 0,
                $producto->MLiCaja ?? 0,
                $producto->CJiCRTN ?? 0,
                $producto->Inventario ?? 0
            );
        }

        return $csv;
    }

    /**
     * Exportar a Excel (formato más completo)
     *
     * @param array $filters
     * @return string CSV content con más columnas
     */
    public function exportarExcel(array $filters = []): string
    {
        $productos = $this->repository->search($filters);

        $csv = "\xEF\xBB\xBF"; // BOM UTF-8
        $csv .= "Tipo,Clase,Grupo,Subgrupo,Código,Referencia,Descripción,Precio,Precio Mínimo,ML/Caja,CJ/CRTN,30CJ,60CJ,100CJ,Inventario,Estado Stock\n";

        foreach ($productos as $producto) {
            $estadoStock = $this->getEstadoStock($producto->Inventario ?? 0);
            
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $this->escapeCsv($producto->Tipo),
                $this->escapeCsv($producto->Clase),
                $this->escapeCsv($producto->Grupo),
                $this->escapeCsv($producto->Subgrupo),
                $this->escapeCsv($producto->{'Cod Max'}),
                $this->escapeCsv($producto->Referencia),
                $this->escapeCsv($producto->Descripcion),
                $producto->Precio ?? 0,
                $producto->Minimo ?? 0,
                $producto->MLiCaja ?? 0,
                $producto->CJiCRTN ?? 0,
                $producto->{'30CJ'} ?? 0,
                $producto->{'60CJ'} ?? 0,
                $producto->{'100CJ'} ?? 0,
                $producto->Inventario ?? 0,
                $estadoStock
            );
        }

        return $csv;
    }

    /**
     * Escapar valores para CSV
     */
    private function escapeCsv($value): string
    {
        if (is_null($value)) {
            return '';
        }
        return str_replace('"', '""', $value);
    }

    /**
     * Obtener estado de stock
     */
    private function getEstadoStock(int $inventario): string
    {
        if ($inventario === 0) return 'Sin Stock';
        if ($inventario < 10) return 'Stock Bajo';
        if ($inventario < 50) return 'Stock Medio';
        return 'Stock Alto';
    }

    /**
     * Obtener detalles de un producto
     *
     * @param string $codigo
     * @return array|null
     */
    public function getProductoDetalle(string $codigo): ?array
    {
        $producto = $this->repository->findByCodigo($codigo);

        if (!$producto) {
            return null;
        }

        return [
            'tipo' => $producto->Tipo,
            'clase' => $producto->Clase,
            'grupo' => $producto->Grupo,
            'subgrupo' => $producto->Subgrupo,
            'codigo' => $producto->{'Cod Max'},
            'referencia' => $producto->Referencia,
            'descripcion' => $producto->Descripcion,
            'precio' => $producto->Precio,
            'precio_formateado' => $producto->precio_formateado,
            'minimo' => $producto->Minimo,
            'minimo_formateado' => $producto->minimo_formateado,
            'ml_caja' => $producto->MLiCaja,
            'cj_crtn' => $producto->CJiCRTN,
            'precio_30cj' => $producto->{'30CJ'},
            'precio_60cj' => $producto->{'60CJ'},
            'precio_100cj' => $producto->{'100CJ'},
            'inventario' => $producto->Inventario,
            'hay_stock' => $producto->hay_stock,
            'estado_inventario' => $producto->estado_inventario,
            'info_embalaje' => $producto->info_embalaje,
        ];
    }

    /**
     * Obtener datos para filtros del frontend
     *
     * @return array
     */
    public function getFiltrosData(): array
    {
        return [
            'tipos' => $this->repository->getTipos(),
            'clases' => $this->repository->getClases(),
            'grupos' => $this->repository->getGrupos(),
            'lineas' => $this->repository->getLineas(), // Alias de tipos
            'estadisticas' => $this->repository->getEstadisticas(),
        ];
    }

    /**
     * Validar y sanitizar parámetros de búsqueda
     *
     * @param array $params
     * @return array
     */
    public function sanitizarParametros(array $params): array
    {
        $allowedOrderFields = ['Tipo', 'Clase', 'Grupo', 'Cod Max', 'Referencia', 'Precio', 'Inventario'];
        
        return [
            'search' => !empty($params['search']) ? trim($params['search']) : null,
            'tipo' => !empty($params['tipo']) ? trim($params['tipo']) : null,
            'clase' => !empty($params['clase']) ? trim($params['clase']) : null,
            'grupo' => !empty($params['grupo']) ? trim($params['grupo']) : null,
            'linea' => !empty($params['linea']) ? trim($params['linea']) : null,
            'solo_con_stock' => filter_var($params['solo_con_stock'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'order_by' => in_array($params['order_by'] ?? '', $allowedOrderFields) 
                ? $params['order_by'] 
                : 'Tipo',
            'order_direction' => in_array($params['order_direction'] ?? '', ['asc', 'desc']) 
                ? $params['order_direction'] 
                : 'asc',
            'per_page' => min(max((int)($params['per_page'] ?? 15), 5), 100),
        ];
    }

    /**
     * Buscar productos similares o relacionados
     *
     * @param string $codigo
     * @param int $limit
     * @return Collection
     */
    public function getProductosRelacionados(string $codigo, int $limit = 5): Collection
    {
        $producto = $this->repository->findByCodigo($codigo);
        
        if (!$producto) {
            return collect([]);
        }

        // Buscar productos del mismo grupo o clase
        $filters = [
            'grupo' => $producto->Grupo,
            'solo_con_stock' => true,
        ];

        $relacionados = $this->repository->search($filters);
        
        // Excluir el producto actual y limitar resultados
        return $relacionados
            ->where('Cod Max', '!=', $codigo)
            ->take($limit);
    }
}