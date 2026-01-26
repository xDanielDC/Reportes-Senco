<?php

namespace App\Repositories;

use App\Models\ListaPrecio;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ListaPreciosRepository
{
    /**
     * Constructor
     */
    public function __construct(
        protected ListaPrecio $model
    ) {}

    /**
     * Obtener productos con paginación y filtros
     *
     * @param array $filters Filtros de búsqueda
     * @param int $perPage Elementos por página
     * @return LengthAwarePaginator
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // Aplicar búsqueda general
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Filtrar por Tipo
        if (!empty($filters['tipo'])) {
            $query->filterByTipo($filters['tipo']);
        }

        // Filtrar por Clase
        if (!empty($filters['clase'])) {
            $query->filterByClase($filters['clase']);
        }

        // Filtrar por Grupo
        if (!empty($filters['grupo'])) {
            $query->filterByGrupo($filters['grupo']);
        }

        // Filtrar por línea (alias de tipo)
        if (!empty($filters['linea'])) {
            $query->filterByLinea($filters['linea']);
        }

        // Filtrar solo productos con stock
        if (!empty($filters['solo_con_stock'])) {
            $query->withStock();
        }

        // Ordenamiento
        $orderBy = $filters['order_by'] ?? 'Tipo';
        $orderDirection = $filters['order_direction'] ?? 'asc';
        $query->orderByField($orderBy, $orderDirection);

        return $query->paginate($perPage);
    }

    /**
     * Buscar productos (sin paginación, para exportar)
     *
     * @param array $filters
     * @return Collection
     */
    public function search(array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['tipo'])) {
            $query->filterByTipo($filters['tipo']);
        }

        if (!empty($filters['clase'])) {
            $query->filterByClase($filters['clase']);
        }

        if (!empty($filters['grupo'])) {
            $query->filterByGrupo($filters['grupo']);
        }

        if (!empty($filters['linea'])) {
            $query->filterByLinea($filters['linea']);
        }

        if (!empty($filters['solo_con_stock'])) {
            $query->withStock();
        }

        $orderBy = $filters['order_by'] ?? 'Tipo';
        $orderDirection = $filters['order_direction'] ?? 'asc';
        $query->orderByField($orderBy, $orderDirection);

        return $query->get();
    }

    /**
     * Obtener un producto por código
     *
     * @param string $codigo
     * @return ListaPrecio|null
     */
    public function findByCodigo(string $codigo): ?ListaPrecio
    {
        return $this->model->where('Cod Max', $codigo)->first();
    }

    /**
     * Obtener todas las líneas (tipos) disponibles
     *
     * @return array
     */
    public function getLineas(): array
    {
        return ListaPrecio::getLineasDisponibles();
    }

    /**
     * Obtener todos los tipos disponibles
     *
     * @return array
     */
    public function getTipos(): array
    {
        return ListaPrecio::getTiposDisponibles();
    }

    /**
     * Obtener todas las clases disponibles
     *
     * @return array
     */
    public function getClases(): array
    {
        return ListaPrecio::getClasesDisponibles();
    }

    /**
     * Obtener todos los grupos disponibles
     *
     * @return array
     */
    public function getGrupos(): array
    {
        return ListaPrecio::getGruposDisponibles();
    }

    /**
     * Obtener estadísticas generales
     *
     * @return array
     */
    public function getEstadisticas(): array
    {
        return [
            'total_productos' => $this->model->count(),
            'productos_con_stock' => $this->model->withStock()->count(),
            'total_tipos' => count($this->getTipos()),
            'total_clases' => count($this->getClases()),
            'total_grupos' => count($this->getGrupos()),
            'precio_promedio' => $this->model->avg('Precio'),
            'precio_minimo' => $this->model->min('Precio'),
            'precio_maximo' => $this->model->max('Precio'),
            'inventario_total' => $this->model->sum('Inventario'),
        ];
    }

    /**
     * Obtener productos por rango de precio
     *
     * @param float $min
     * @param float $max
     * @return Collection
     */
    public function getByPriceRange(float $min, float $max): Collection
    {
        return $this->model
            ->whereBetween('Precio', [$min, $max])
            ->orderBy('Precio')
            ->get();
    }

    /**
     * Obtener productos más vendidos o destacados
     * (puedes ajustar el criterio según tu lógica de negocio)
     *
     * @param int $limit
     * @return Collection
     */
    public function getDestacados(int $limit = 10): Collection
    {
        return $this->model
            ->withStock()
            ->orderBy('Inventario', 'desc')
            ->limit($limit)
            ->get();
    }
}