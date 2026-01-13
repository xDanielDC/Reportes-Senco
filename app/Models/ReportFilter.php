<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportFilter extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'table',
        'column',
        'operator',
        'values',
    ];

    /**
     * @var string[]
     */
    protected $appends = [
        'parse_values',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s A',
    ];

    public function getParseValuesAttribute(): array|string
    {
        return $this->operator === 'In' ? explode(',', $this->values) : strtolower($this->values);
    }
}
