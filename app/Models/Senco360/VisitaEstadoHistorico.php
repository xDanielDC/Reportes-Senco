<?php
namespace App\Models\Senco360;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class VisitaEstadoHistorico extends Model
{
    protected $connection = 'senco360';
    protected $table = 'RT_visita_estado_historico';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    protected $fillable = [
        'ID_ENC_VISITA', 'ID_ESTADO', 'FECHA', 'OBSERVACIONES', 'ID_USUARIO', 'ID_SOLICITUD_PARTE'
    ];

    public function estado()
    {
        return $this->belongsTo(VisitaEstado::class, 'ID_ESTADO', 'ID');
    }

    public function usuario()
    {
        return $this->belongsTo(\App\Models\User::class, 'ID_USUARIO', 'id');
    }

    public function solicitudParte()
    {
        return $this->belongsTo(SolicitudParte::class, 'ID_SOLICITUD_PARTE', 'ID');
    }

    // Normaliza FECHA a un formato que SQL Server interpreta
    // de forma independiente del idioma/DATEFORMAT de la sesión.
    // Evita el error 22007 (conversión nvarchar -> datetime).
    public function setFechaAttribute($value)
    {
        if ($value instanceof \DateTimeInterface) {
            $this->attributes['FECHA'] = $value->format('Ymd H:i:s');
        } elseif (empty($value)) {
            $this->attributes['FECHA'] = Carbon::now()->format('Ymd H:i:s');
        } else {
            $this->attributes['FECHA'] = Carbon::parse($value)->format('Ymd H:i:s');
        }
    }
}