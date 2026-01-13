<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

class Report extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'name', 'group_id', 'report_id', 'access_level', 'dataset_id', 'user_id', 'token', 'expiration_date'
    ];

    /**
     * @var string[]
     */
    protected $appends = [
        'filter_array',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d h:i:s A',
        'updated_at' => 'datetime:Y-m-d h:i:s A',
        'expiration_date' => 'datetime'
    ];

    /**
     * @return HasOne
     */
    public function created_by(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * @return BelongsToMany
     */
    public function user(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_reports')
            ->withPivot('report_id', 'user_id');
    }

    /**
     * @return BelongsToMany
     */
    public function filters(): BelongsToMany
    {
        return $this->belongsToMany(ReportFilter::class, 'pvt_report_user_filters', 'report_id', 'filter_id')
            ->where('user_id', '=', Auth::id())
            ->withPivot('user_id');
    }

    /**
     * @return string|false
     */
    public function getFilterArrayAttribute(): bool|string
    {
        $filters = $this->filters->toArray();
        $filters = collect($filters);

        $filters = $filters->map(function ($row) {
            return [
                '$schema' => 'http://powerbi.com/product/schema#basic',
                'target' => [
                    'table' => $row['table'],
                    'column' => $row['column'],
                ],
                'operator' => $row['operator'],
                'values' => $row['parse_values'],
            ];
        });

        return json_encode($filters);
    }
}
