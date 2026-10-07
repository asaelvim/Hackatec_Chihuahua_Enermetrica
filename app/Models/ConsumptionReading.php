<?php

namespace App\Models;

use Database\Factories\ConsumptionReadingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ConsumptionReading extends Model
{
    /** @use HasFactory<ConsumptionReadingFactory> */
    use HasFactory;

    protected $fillable = ['device_id', 'value', 'read_at'];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'value' => 'decimal:2',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function anomaly(): HasOne
    {
        return $this->hasOne(Anomaly::class);
    }
}
