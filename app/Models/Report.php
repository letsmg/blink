<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'generated_by',
        'title',
        'type',
        'filters',
        'data',
        'period_start',
        'period_end',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'json',
            'data' => 'json',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    /**
     * User who generated this report.
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
