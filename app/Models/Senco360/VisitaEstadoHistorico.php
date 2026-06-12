<?php
namespace App\Models\Senco360;

use Illuminate\Database\Eloquent\Model;

class VisitaEstadoHistorico extends Model
{
    protected $connection = 'senco360';
    protected $table = 'RT_visita_estado_historico';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    protected $fillable = [
        'ID_ENC_VISITA', 'ID_ESTADO', 'FECHA', 'OBSERVACIONES', 'ID_USUARIO'
    ];

    public function estado()
    {
        return $this->belongsTo(VisitaEstado::class, 'ID_ESTADO', 'ID');
    }

    public function usuario()
    {
        return $this->belongsTo(\App\Models\User::class, 'ID_USUARIO', 'id');
    }
}