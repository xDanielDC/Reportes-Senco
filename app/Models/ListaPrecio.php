<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ListaPrecio extends Model
{
    /**
     * Indica que este modelo no usa timestamps
     */
    public $timestamps = false;

    /**
     * IMPORTANTE: Usar conexión SQL Server
     */
    protected $connection = 'sqlsrv';

    /**
     * La vista de base de datos asociada al modelo
     */
    protected $table = 'dbo.Senco_View_listaprecios_inventario';

    /**
     * La clave primaria del modelo (usaremos Cod Max como identificador)
     */
    protected $primaryKey = 'Cod Max';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Atributos asignables en masa (basados en la imagen proporcionada)
     */
    protected $fillable = [
        'Tipo',
        'Clase',
        'Grupo',
        'Subgrupo',
        'Referencia',
        'Cod Max',
        'Descripcion',
        'MLiCaja',
        'CJiCRTN',
        'Precio',
        'Minimo',
        '30CJ',
        '60CJ',
        '100CJ',
        'Inventario',
    ];

    /**
     * Casting de atributos
     */
    protected $casts = [
        'Precio' => 'decimal:2',
        'Minimo' => 'decimal:2',
        'MLiCaja' => 'integer',
        'CJiCRTN' => 'integer',
        '30CJ' => 'integer',
        '60CJ' => 'integer',
        '100CJ' => 'integer',
        'Inventario' => 'integer',
    ];

    /**
     * Scope para buscar por cualquier campo relevante
     */
    public function scopeSearch($query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('Cod Max', 'LIKE', "%{$search}%")
              ->orWhere('Referencia', 'LIKE', "%{$search}%")
              ->orWhere('Descripcion', 'LIKE', "%{$search}%")
              ->orWhere('Clase', 'LIKE', "%{$search}%")
              ->orWhere('Grupo', 'LIKE', "%{$search}%")
              ->orWhere('Subgrupo', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Scope para filtrar por Tipo
     */
    public function scopeFilterByTipo($query, $tipo)
    {
        if (empty($tipo)) {
            return $query;
        }

        return $query->where('Tipo', $tipo);
    }

    /**
     * Scope para filtrar por Clase
     */
    public function scopeFilterByClase($query, $clase)
    {
        if (empty($clase)) {
            return $query;
        }

        return $query->where('Clase', $clase);
    }

    /**
     * Scope para filtrar por Grupo
     */
    public function scopeFilterByGrupo($query, $grupo)
    {
        if (empty($grupo)) {
            return $query;
        }

        return $query->where('Grupo', $grupo);
    }

    /**
     * Scope para filtrar por línea (alias de Tipo)
     */
    public function scopeFilterByLinea($query, $linea)
    {
        return $this->scopeFilterByTipo($query, $linea);
    }

    /**
     * Scope para filtrar por stock disponible
     */
    public function scopeWithStock($query)
    {
        return $query->where('Inventario', '>', 0);
    }

    /**
     * Scope para ordenar resultados
     */
    public function scopeOrderByField($query, $field = 'Tipo', $direction = 'asc')
    {
        $allowedFields = [
            'Tipo',
            'Clase',
            'Grupo',
            'Subgrupo',
            'Cod Max',
            'Referencia',
            'Descripcion',
            'Precio',
            'Inventario',
        ];
        
        if (in_array($field, $allowedFields)) {
            return $query->orderBy($field, $direction);
        }

        return $query->orderBy('Tipo', 'asc');
    }

    /**
     * Obtener todos los Tipos únicos
     */
    public static function getTiposDisponibles()
{
    return self::query()
        ->select('Tipo')
        ->distinct()
        ->whereNotNull('Tipo')
        ->orderBy('Tipo')
        ->pluck('Tipo')
        ->toArray();
}

    /**
     * Obtener todas las Clases únicas
     */
    public static function getClasesDisponibles()
{
    return self::query()
        ->select('Clase')
        ->distinct()
        ->whereNotNull('Clase')
        ->orderBy('Clase')
        ->pluck('Clase')
        ->toArray();
}

    /**
     * Obtener todos los Grupos únicos
     */
    public static function getGruposDisponibles()
{
    return self::query()
        ->select('Grupo')
        ->distinct()
        ->whereNotNull('Grupo')
        ->orderBy('Grupo')
        ->pluck('Grupo')
        ->toArray();
}

    /**
     * Alias para compatibilidad con el código existente
     */
    public static function getLineasDisponibles()
    {
        return self::getTiposDisponibles();
    }

    /**
     * ACCESSORS - Para normalizar nombres al frontend
     */
    
    public function getLineaAttribute()
    {
        return $this->attributes['Tipo'] ?? null;
    }

    public function getCodigoAttribute()
    {
        return $this->attributes['Cod Max'] ?? null;
    }

    public function getReferenciaAttribute()
    {
        return $this->attributes['Referencia'] ?? null;
    }

    public function getDescripcionAttribute()
    {
        return $this->attributes['Descripcion'] ?? null;
    }

    public function getPrecioAttribute()
    {
        return $this->attributes['Precio'] ?? 0;
    }

    public function getStockAttribute()
    {
        return $this->attributes['Inventario'] ?? 0;
    }

    /**
     * Accesor para formatear el precio
     */
    public function getPrecioFormateadoAttribute()
    {
        $precio = $this->attributes['Precio'] ?? 0;
        return '$' . number_format($precio, 0, ',', '.');
    }

    /**
     * Accesor para precio mínimo formateado
     */
    public function getMinimoFormateadoAttribute()
    {
        $minimo = $this->attributes['Minimo'] ?? 0;
        return '$' . number_format($minimo, 0, ',', '.');
    }

    /**
     * Accesor para indicar si hay stock
     */
    public function getHayStockAttribute()
    {
        $stock = $this->attributes['Inventario'] ?? 0;
        return $stock > 0;
    }

    /**
     * Accesor para el estado del inventario
     */
    public function getEstadoInventarioAttribute()
    {
        $inventario = $this->attributes['Inventario'] ?? 0;
        
        if ($inventario === 0) {
            return 'sin_stock';
        } elseif ($inventario < 10) {
            return 'bajo';
        } elseif ($inventario < 50) {
            return 'medio';
        }
        
        return 'alto';
    }

    /**
     * Información de embalaje
     */
    public function getInfoEmbalajeAttribute()
    {
        return [
            'ml_por_caja' => $this->attributes['MLiCaja'] ?? 0,
            'cajas_por_carton' => $this->attributes['CJiCRTN'] ?? 0,
            'precio_30cj' => $this->attributes['30CJ'] ?? 0,
            'precio_60cj' => $this->attributes['60CJ'] ?? 0,
            'precio_100cj' => $this->attributes['100CJ'] ?? 0,
        ];
    }
}