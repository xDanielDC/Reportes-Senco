<?php
namespace App\Models\Senco360;

use Illuminate\Database\Eloquent\Model;

class TipoMant extends Model
{
    protected $connection = 'senco360';
    protected $table = 'RT_tipo_mant';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    protected $fillable = ['TIPO_MANT', 'DESCRIPCION'];
}
