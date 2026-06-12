<?php
namespace App\Models\Senco360;

use Illuminate\Database\Eloquent\Model;

class VisitaFoto extends Model
{
    protected $connection = 'senco360';
    protected $table = 'RT_visita_fotos';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_DETALLE_VISITA',
        'TIPO',
        'RUTA_FOTO',
    ];

    public function detalle()
    {
        return $this->belongsTo(VisitaDetal::class, 'ID_DETALLE_VISITA', 'ID');
    }
}