<?php
namespace App\Models\Senco360;

use Illuminate\Database\Eloquent\Model;

class TipoServicio extends Model
{
    protected $connection = 'senco360';
    protected $table = 'RT_tipo_servicio';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    protected $fillable = ['TIPO_SERVICIO', 'DESCRIPCION'];
}