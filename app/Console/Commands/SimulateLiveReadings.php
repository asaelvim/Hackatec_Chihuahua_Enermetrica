<?php

namespace App\Console\Commands;

use App\Models\Area;
use App\Models\ConsumptionReading;
use App\Models\Device;
use App\Models\DeviceModel;
use App\Models\DeviceType;
use App\Services\ConsumptionIngestionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Genera una lectura de consumo "en vivo" para un dispositivo simulado,
 * pensado para correr cada minuto desde el scheduler (igual que
 * MarkOfflineDevices/SummarizeDailyConsumption). A diferencia de
 * app:seed-demo-data (que rellena historia de golpe), este comando añade
 * una sola lectura nueva cada vez que se ejecuta, para que el dashboard se
 * vea "vivo" sin necesidad de hardware real.
 *
 * El valor es un paseo aleatorio con reversión a la media (se mantiene
 * estable la mayoría del tiempo) y ocasionalmente dispara un "evento"
 * (bajón o subida) que se recupera solo en las siguientes lecturas.
 */
#[Signature('app:simulate-live-readings {--watts=450 : Consumo base en watts} {--device= : Nombre del dispositivo simulado}')]
#[Description('Genera una lectura de consumo artificial para simular un dispositivo en tiempo real')]
class SimulateLiveReadings extends Command
{
    public function handle(ConsumptionIngestionService $ingestionService): int
    {
        $baseWatts = (float) $this->option('watts');
        $deviceName = (string) ($this->option('device') ?: 'Simulador en vivo');

        $device = $this->resolveDevice($deviceName);

        $lastReading = ConsumptionReading::query()
            ->where('device_id', $device->id)
            ->latest('read_at')
            ->first();

        $current = $lastReading?->value ?? $baseWatts;

        $value = $this->nextValue($current, $baseWatts);

        $result = $ingestionService->ingest($device, round($value, 2));

        $this->info(sprintf(
            '[%s] %s -> %.2f W%s',
            $device->name,
            now()->format('H:i:s'),
            $value,
            $result['anomaly'] ? ' (anomalía detectada)' : ''
        ));

        return self::SUCCESS;
    }

    /**
     * Busca (o crea la primera vez) el dispositivo simulado, dejándolo
     * "encendido" para que las lecturas no se descarten por estar apagado.
     */
    private function resolveDevice(string $name): Device
    {
        $device = Device::where('name', $name)->first();

        if ($device) {
            return $device;
        }

        $area = Area::firstOrCreate(['name' => 'Monitoreo']);
        $type = DeviceType::firstOrCreate(['name' => 'Simulación']);
        $model = DeviceModel::firstOrCreate(['name' => 'Generador de lecturas virtual']);

        return Device::create([
            'name' => $name,
            'area_id' => $area->id,
            'device_type_id' => $type->id,
            'device_model_id' => $model->id,
            'status' => 'on',
        ]);
    }

    /**
     * Paseo aleatorio con reversión a la media: casi siempre se mueve poco
     * alrededor de la base, pero ~6% de las veces dispara un evento más
     * notorio (bajón o subida) que luego la reversión a la media va
     * corrigiendo en las siguientes lecturas.
     */
    private function nextValue(float $current, float $baseWatts): float
    {
        // Reversión a la media: empuja el valor de vuelta hacia la base ~15% cada tick.
        $meanReversion = ($baseWatts - $current) * 0.15;

        // Ruido normal, pequeño (~3% de la base), para que se note variación fina.
        $noise = $this->randomBetween(-0.03, 0.03) * $baseWatts;

        $value = $current + $meanReversion + $noise;

        // ~6% de probabilidad de un evento notorio: bajón (apagado parcial) o pico de consumo.
        if (random_int(1, 100) <= 6) {
            $isDip = random_int(0, 1) === 0;
            $magnitude = $this->randomBetween(0.25, 0.5) * $baseWatts;
            $value += $isDip ? -$magnitude : $magnitude;
        }

        return max(0, $value);
    }

    private function randomBetween(float $min, float $max): float
    {
        return $min + (random_int(0, 10000) / 10000) * ($max - $min);
    }
}
