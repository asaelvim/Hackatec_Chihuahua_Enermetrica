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

    /**
     * Apaga (status = off) los dispositivos que este controla a través de
     * sus relevadores y que actualmente estén encendidos. Se usa cuando
     * este dispositivo (normalmente el ESP32) deja de estar "on", ya que
     * en ese caso ya no puede sostener sus relevadores encendidos.
     */
    public function turnOffControlledRelayDevices(?User $actor = null): void
    {
        $this->relayDevices()
            ->where('status', 'on')
            ->get()
            ->each(fn (Device $relayDevice) => $relayDevice->update([
                'status' => 'off',
                'commanded_at' => now(),
                'commanded_by' => $actor?->id,
            ]));
    }

    /**
     * Enciende (status = on) los dispositivos que este controla a través
     * de sus relevadores y que actualmente estén apagados. Se usa cuando
     * este dispositivo (normalmente el ESP32) pasa a estar "on".
     * No toca los que estén "offline"/"maintenance" (esos se cambian
     * forzosamente solo desde Editar).
     */
    public function turnOnControlledRelayDevices(?User $actor = null): void
    {
        $this->relayDevices()
            ->where('status', 'off')
            ->get()
            ->each(fn (Device $relayDevice) => $relayDevice->update([
                'status' => 'on',
                'commanded_at' => now(),
                'commanded_by' => $actor?->id,
            ]));
    }

    /**
     * Si este dispositivo es un relevador y su controlador (el ESP32)
     * está "off", lo enciende también: el controlador se considera "on"
     * en cuanto al menos uno de sus relevadores está encendido.
     */
    protected function syncControllerOnAfterTurningOn(): void
    {
        if (! $this->isRelayControlled()) {
            return;
        }

        $controller = $this->controller;

        if ($controller && $controller->status === 'off') {
            $controller->update(['status' => 'on']);
        }
    }

    /**
     * Si este dispositivo es un relevador y, tras apagarlo, ya ninguno de
     * los relevadores de su controlador (el ESP32) sigue encendido, apaga
     * también al controlador.
     */
    protected function syncControllerOffAfterTurningOff(): void
    {
        if (! $this->isRelayControlled()) {
            return;
        }

        $controller = $this->controller;

        if (! $controller || $controller->status !== 'on') {
            return;
        }

        $algunoEncendido = $controller->relayDevices()->where('status', 'on')->exists();

        if (! $algunoEncendido) {
            $controller->update(['status' => 'off']);
        }
    }

    /**
     * Enciende/apaga con un clic (desde el panel web o la app móvil).
     * No hace nada si el estado actual es "offline"/"maintenance" (esos
     * solo se cambian forzosamente desde Editar). Devuelve si se aplicó.
     *
     * Mantiene sincronizado el estado del ESP32 con sus relevadores: al
     * encender el ESP32 se encienden todos sus relevadores, al apagarlo
     * se apagan todos; y si se enciende/apaga un relevador individual, el
     * ESP32 se enciende (si estaba apagado) o se apaga (si era el último
     * relevador encendido), respectivamente.
     */
    public function toggleStatus(?User $actor = null): bool
    {
        if (! $this->isTogglable()) {
            return false;
        }

        $newStatus = $this->status === 'on' ? 'off' : 'on';

        $this->update(array_merge(
            ['status' => $newStatus],
            $this->isRelayControlled()
                ? ['commanded_at' => now(), 'commanded_by' => $actor?->id]
                : []
        ));

        if ($this->isRelayControlled()) {
            $newStatus === 'on'
                ? $this->syncControllerOnAfterTurningOn()
                : $this->syncControllerOffAfterTurningOff();
        } else {
            $newStatus === 'on'
                ? $this->turnOnControlledRelayDevices($actor)
                : $this->turnOffControlledRelayDevices($actor);
        }

        return true;
    }
}
