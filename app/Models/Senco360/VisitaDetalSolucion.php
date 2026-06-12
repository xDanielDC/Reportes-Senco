<?php
namespace App\Models\Senco360;

use Illuminate\Database\Eloquent\Relations\Pivot;

class VisitaDetalSolucion extends Pivot
{
    protected $connection = 'senco360';
    protected $table = 'RT_visita_detal_solucion';
    public $timestamps = false;

    protected $fillable = [
        'ID_DETALLE_VISITA',
        'ID_SOLUCION',
    ];
}
