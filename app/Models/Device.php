<?php

namespace App\Models;

use Database\Factories\DeviceFactory;
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
        'controller_device_id',
        'relay_channel',
        'reported_status',
        'commanded_at',
        'commanded_by',
        'reported_at',
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
            'commanded_at' => 'datetime',
            'reported_at' => 'datetime',
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

    /**
     * El ESP32 (u otro dispositivo) que controla físicamente este
     * dispositivo a través de uno de sus 5 relevadores.
     */
    public function controller(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'controller_device_id');
    }

    /**
     * Los dispositivos (p.ej. "Aire acondicionado", "Compresor") cuyo
     * encendido/apagado controla este dispositivo a través de sus 5
     * relevadores.
     */
    public function relayDevices(): HasMany
    {
        return $this->hasMany(Device::class, 'controller_device_id')->orderBy('relay_channel');
    }

    public function commandedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'commanded_by');
    }

    /**
     * Un dispositivo solo se puede encender/apagar con un clic (desde la
     * lista) cuando su estado es "on"/"off"; "offline"/"maintenance" se
     * cambian forzosamente solo desde Editar.
     */
    public function isTogglable(): bool
    {
        return in_array($this->status, ['on', 'off'], true);
    }

    public function isRelayControlled(): bool
    {
        return $this->controller_device_id !== null;
    }
}
