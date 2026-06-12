<?php
namespace App\Models\Senco360;

use Illuminate\Database\Eloquent\Model;

class TipoFalla extends Model
{
    protected $connection = 'senco360';
    protected $table = 'RT_tipo_falla';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    protected $fillable = ['TIPO_FALLA', 'DESCRIPCION'];
}
