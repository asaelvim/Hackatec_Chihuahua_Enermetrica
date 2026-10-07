<?php

namespace App\Models;

use Database\Factories\DailyConsumptionSummaryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyConsumptionSummary extends Model
{
    /** @use HasFactory<DailyConsumptionSummaryFactory> */
    use HasFactory;

    protected $fillable = [
        'device_id',
        'date',
        'total_kwh',
        'avg_watts',
        'min_watts',
        'max_watts',
        'readings_count',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_kwh' => 'decimal:3',
            'avg_watts' => 'decimal:2',
            'min_watts' => 'decimal:2',
            'max_watts' => 'decimal:2',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
