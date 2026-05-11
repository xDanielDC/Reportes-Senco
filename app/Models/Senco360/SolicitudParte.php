<?php
namespace App\Models\Senco360;

use Illuminate\Database\Eloquent\Model;

class SolicitudParte extends Model
{
    protected $connection = 'senco360';
    protected $table = 'RT_solicitud_partes';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_DETALLE_VISITA', 'ID_COD_MAX_PARTES',
        'CANTIDAD', 'ID_ESTADO', 'OBSERVACION',
    ];

    public function detalle()
    {
        return $this->belongsTo(VisitaDetal::class, 'ID_DETALLE_VISITA', 'ID');
    }

    public function estado()
    {
        return $this->belongsTo(VisitaEstado::class, 'ID_ESTADO', 'ID');
    }
}
