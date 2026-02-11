<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RutaTecnica extends Model
{
    protected $connection = 'senco360';
    protected $table = 'RT_rutastecnicas';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $primaryKey = 'idVisita';
    public $timestamps = false;

    protected $fillable = [
        'NumeroRuta',
        'FechaInicio',
        'FechaFin',
        'Nit',
        'NombreCliente',
        'DireccionCompleta',
        'FechaVisita',
        'NomContacto',
        'TelContacto',
        'CodVendedor',
        'CodTecnico',
        'Observaciones',
    ];

    protected $casts = [
        'FechaInicio' => 'date',
        'FechaFin' => 'date',
        'fechaVisita' => 'date',
    ];

    /**
     * Relación con visitas
     */
    // public function visitas()
    // {
    //     return $this->hasMany(VisitaRuta::class, 'NumeroRuta', 'NumeroRuta')
    //                 ->orderBy('FechaVisita');
    // }

    /**
     * Relación con usuario (vendedor) - Ajusta según tu modelo User
     */
    public function vendedor()
    {
        return $this->belongsTo(User::class, 'codigo_vendedor', 'codigo_vendedor');
    }

    /**
     * Generar número de ruta automático
     * Formato: RT-YYYYMMDD-XXXX
     */
    public static function generarNumeroRuta(): string
{
    $hoy = Carbon::now()->format('Ymd');

    // Buscar la última ruta creada hoy
    $ultimaRuta = self::where('NumeroRuta', 'like', "RT-$hoy-%")
        ->orderBy('NumeroRuta', 'desc')
        ->value('NumeroRuta');

    if (!$ultimaRuta) {
        // Primera ruta del día
        $consecutivo = 1;
    } else {
        // RT-YYYYMMDD-000X
        $partes = explode('-', $ultimaRuta);

        // Seguridad extra
        $ultimoConsecutivo = isset($partes[2])
            ? intval($partes[2])
            : 0;

        $consecutivo = $ultimoConsecutivo + 1;
    }

    return sprintf('RT-%s-%04d', $hoy, $consecutivo);
}

    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->where(function($q) use ($fechaInicio, $fechaFin) {
            $q->whereBetween('FechaInicio', [$fechaInicio, $fechaFin])
              ->orWhereBetween('FechaFin', [$fechaInicio, $fechaFin]);
        });
    }

    /**
     * Scope para filtrar por vendedor
     */
    public function scopePorVendedor($query, $codVendedor)
    {
        return $query->where('CodVendedor', $codVendedor);
    }
}
