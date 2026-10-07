<?php

namespace App\Models;

use Database\Factories\AnomalyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Anomaly extends Model
{
    /** @use HasFactory<AnomalyFactory> */
    use HasFactory;

    protected $fillable = [
        'device_id',
        'consumption_reading_id',
        'z_score',
        'value',
        'notified_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'z_score' => 'decimal:4',
            'value' => 'decimal:2',
            'notified_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function consumptionReading(): BelongsTo
    {
        return $this->belongsTo(ConsumptionReading::class);
    }
}
