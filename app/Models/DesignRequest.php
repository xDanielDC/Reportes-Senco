<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DesignRequest extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'comments', 'reception_date', 'tentative_date', 'real_date', 'delivery_date',
        'customer_approved_date', 'estimated_arrival_sherpa_date',  'observations',
        'priority_id', 'designer_id', 'seller_document', 'customer_id',
        'time_state_id', 'state_id', 'created_id', 'updated_id',
    ];

    /**
     * @var string[]
     */
    protected $hidden = [
        'priority_id', 'designer_id', 'seller_document', 'customer_id',
        'time_state_id', 'state_id', 'created_id', 'updated_id',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
        'reception_date' => 'datetime:Y-m-d',
        'tentative_date' => 'datetime:Y-m-d',
        'real_date' => 'datetime:Y-m-d',
        'delivery_date' => 'datetime:Y-m-d',
        'customer_approved_date' => 'datetime:Y-m-d',
        'estimated_arrival_sherpa_date' => 'datetime:Y-m-d',
    ];

    /**
     * @var string[]
     */
    protected $with = [
        'priority', 'designer', 'seller', 'customer', 'time_state',
        'state', 'created_by', 'updated_by', 'tasks',
    ];

    public function priority(): HasOne
    {
        return $this->hasOne(DesignPriority::class, 'id', 'priority_id');
    }

    public function designer(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'designer_id');
    }

    public function seller(): HasOne
    {
        return $this->hasOne(Seller::class, 'tgecodigo', 'seller_document');
    }

    public function customer(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'customer_id');
    }

    public function time_state(): HasOne
    {
        return $this->hasOne(DesignTimeState::class, 'id', 'time_state_id');
    }

    public function state(): HasOne
    {
        return $this->hasOne(DesignState::class, 'id', 'state_id');
    }

    public function created_by(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'created_id');
    }

    public function updated_by(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'updated_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(DesignTask::class, 'design_request_id', 'id');
    }
}
