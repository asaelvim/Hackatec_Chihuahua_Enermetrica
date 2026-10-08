<?php

namespace App\Models;

use Database\Factories\DeviceRelayChannelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Representa uno de los relevadores físicos de un dispositivo (1-5). El
 * ESP32 consulta periódicamente `desired_state` y, al aplicarlo, reporta
 * `reported_state` para que el panel sepa que el comando sí se ejecutó.
 */
class DeviceRelayChannel extends Model
{
    /** @use HasFactory<DeviceRelayChannelFactory> */
    use HasFactory;

    protected $fillable = [
        'device_id',
        'channel',
        'label',
        'desired_state',
        'reported_state',
        'commanded_at',
        'commanded_by',
        'reported_at',
    ];

    protected function casts(): array
    {
        return [
            'commanded_at' => 'datetime',
            'reported_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function commandedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'commanded_by');
    }
}
