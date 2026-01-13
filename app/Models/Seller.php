<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $connection = 'ssf';

    /**
     * @var string
     */
    protected $table = 'un_tercegener';

    /**
     * @var string[]
     */
    protected $fillable = [
        'tgecodigo', 'tgenombcomp',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('filter', function (Builder $builder) {
            $builder->select('tgecodigo', 'tgenombcomp')
                ->where('eobcodigo', '=', 'AC')
                ->where('tgeesvendcobr', '=', 'S')
                ->distinct();
        });
    }
}
