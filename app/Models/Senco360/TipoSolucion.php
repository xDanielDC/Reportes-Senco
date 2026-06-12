<?php
namespace App\Models\Senco360;

use Illuminate\Database\Eloquent\Model;

class TipoSolucion extends Model
{
    protected $connection = 'senco360';
    protected $table = 'RT_tipo_solucion';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    protected $fillable = ['TIPO_SOLUCION', 'DESCRIPCION'];
}