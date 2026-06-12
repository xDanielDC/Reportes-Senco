<?php
namespace App\Models\Senco360;

use Illuminate\Database\Eloquent\Model;

class VisitaDetal extends Model
{
    protected $connection = 'senco360';
    protected $table = 'RT_visita_detal';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_ENC_VISITA', 'ID_COD_MAX', 'TITULO','SERIAL',
        'DESCRIPCION_FALLA', 'ID_SOLUCION', 'ID_TIPO_MANT', 'OBSERVACIONES',
    ];

    public function encabezado()
    {
        return $this->belongsTo(VisitaEncab::class, 'ID_ENC_VISITA', 'ID');
    }

    public function tipoSolucion()
    {
        return $this->belongsTo(TipoSolucion::class, 'ID_SOLUCION', 'ID');
    }

    public function tipoMant()
    {
        return $this->belongsTo(TipoMant::class, 'ID_TIPO_MANT', 'ID');
    }

    public function tiposSolucion()
    {
        return $this->belongsToMany(
            TipoSolucion::class,
            'RT_visita_detal_solucion',
            'ID_DETALLE_VISITA',
            'ID_SOLUCION',
            'ID',
            'ID'
        );
    }

    public function tiposFalla()
    {
        return $this->belongsToMany(
            TipoFalla::class,
            'RT_visita_detal_falla',
            'ID_VISITA_DETAL',
            'ID_TIPO_FALLA',
            'ID',
            'ID'
        )->withPivot('DESCRIPCION_OTROS');
    }

    public function solicitudesPartes()
    {
        return $this->hasMany(SolicitudParte::class, 'ID_DETALLE_VISITA', 'ID');
    }

    public function fotos()
    {
        return $this->hasMany(VisitaFoto::class, 'ID_DETALLE_VISITA', 'ID');
    }
}
