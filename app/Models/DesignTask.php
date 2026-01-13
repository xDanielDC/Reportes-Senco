<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DesignTask extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'design_request_id', 'description',
    ];

    /**
     * @var string[]
     */
    protected $hidden = [
        'created_id', 'updated_id',
    ];

    /**
     * @var string[]
     */
    protected $with = [
        'created_by',
        'updated_by',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d h:i:s A',
        'updated_at' => 'datetime:Y-m-d h:i:s A',
    ];

    public function created_by(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'created_id');
    }

    public function updated_by(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'updated_id');
    }
}
