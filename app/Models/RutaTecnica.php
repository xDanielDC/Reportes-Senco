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
        'cerrada',
        'fecha_cierre',
    ];

    protected $casts = [
        'FechaInicio' => 'date',
        'FechaFin' => 'date',
        'fechaVisita' => 'date',
        'cerrada' => 'boolean',
        'fecha_cierre' => 'datetime',
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

    /**
     * Scope para obtener solo rutas abiertas
     */
    public function scopeAbiertas($query)
    {
        return $query->where('cerrada', false);
    }

    /**
     * Determinar si una ruta debe cerrarse automáticamente
     * Se cierra si es viernes después de las 2pm (sin importar la fecha fin)
     */
    public function debeCerrarse(): bool
    {
        // Si ya está cerrada, no necesita cerrarse
        if ($this->cerrada) {
            return false;
        }

        $ahora = Carbon::now();
        $diaSemana = $ahora->dayOfWeek; // 5 = Friday
        $hora = $ahora->hour;

        // Verificar si es viernes después de las 14:00 (2pm)
        // La ruta se cierra el viernes a las 2pm para que se asigne al técnico
        return ($diaSemana === Carbon::FRIDAY && $hora >= 14);
    }

    /**
     * Cerrar la ruta
     */
    public function cerrar(): bool
    {
        return $this->update([
            'cerrada' => true,
            'fecha_cierre' => Carbon::now()
        ]);
    }

    /**
     * Obtener el estado de la ruta (abierta/cerrada)
     */
    public function getEstadoAttribute(): string
    {
        // Si está marcada como cerrada, retornar cerrada
        if ($this->cerrada) {
            return 'cerrada';
        }

        // Verificar si debe cerrarse automáticamente
        if ($this->debeCerrarse()) {
            return 'cerrada';
        }

        return 'abierta';
    }

    /**
     * Cerrar todas las rutas que deberían estar cerradas
     */
    public static function cerrarRutasVencidas()
    {
        $rutas = self::where('cerrada', false)->get();
        
        foreach ($rutas as $ruta) {
            if ($ruta->debeCerrarse()) {
                $ruta->cerrar();
            }
        }
    }
}
