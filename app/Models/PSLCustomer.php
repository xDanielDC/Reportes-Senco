<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PSLCustomer extends Model
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
    protected $fillable = ['tgecodigo', 'tgenombcomp'];

    /***
     * @return void
     */
    protected static function booted(): void
    {
        static::addGlobalScope('filter', function (Builder $builder) {
            $builder->where('eobcodigo ', '=', 'AC')
                ->where('tgeesclie', '=', 'S')
                ->distinct();
        });
    }
}
