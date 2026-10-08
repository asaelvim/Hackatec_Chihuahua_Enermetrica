<?php

namespace App\Models;

use Database\Factories\DeviceFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Device extends Model
{
    /** @use HasFactory<DeviceFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'area_id',
        'device_type_id',
        'device_model_id',
        'status',
        'last_reading_at',
    ];

    protected $hidden = [
        'api_token',
    ];

    protected static function booted(): void
    {
        static::creating(function (Device $device) {
            $device->api_token ??= Str::random(40);
        });
    }

    protected function casts(): array
    {
        return [
            'last_reading_at' => 'datetime',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function deviceType(): BelongsTo
    {
        return $this->belongsTo(DeviceType::class);
    }

    public function deviceModel(): BelongsTo
    {
        return $this->belongsTo(DeviceModel::class);
    }

    public function consumptionReadings(): HasMany
    {
        return $this->hasMany(ConsumptionReading::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function anomalies(): HasMany
    {
        return $this->hasMany(Anomaly::class);
    }

    public function dailyConsumptionSummaries(): HasMany
    {
        return $this->hasMany(DailyConsumptionSummary::class);
    }

    public function relayChannels(): HasMany
    {
        return $this->hasMany(DeviceRelayChannel::class)->orderBy('channel');
    }

    /**
     * Garantiza que existan los 5 canales de relevador para este
     * dispositivo (idempotente), con estado inicial "off". Se usa tanto
     * al consultar/comandar desde el panel como cuando el ESP32 hace
     * polling, para no depender de cuándo se creó el dispositivo.
     *
     * @return Collection<int, DeviceRelayChannel>
     */
    public function ensureRelayChannels(): Collection
    {
        for ($channel = 1; $channel <= 5; $channel++) {
            $this->relayChannels()->firstOrCreate(
                ['channel' => $channel],
                ['label' => "Relevador {$channel}", 'desired_state' => 'off']
            );
        }

        return $this->relayChannels()->get();
    }
}
