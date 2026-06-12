<?php
namespace App\Models\Senco360;

use Illuminate\Database\Eloquent\Model;

class VisitaEstado extends Model
{
    protected $connection = 'senco360';
    protected $table = 'RT_visita_estados';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    protected $fillable = ['ESTADO', 'descripcion'];
}