<?php

namespace App\Console\Commands;

use App\Models\Area;
use App\Models\Device;
use App\Models\DeviceModel;
use App\Models\DeviceType;
use App\Models\Schedule;
use App\Services\ConsumptionIngestionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('app:seed-demo-data {--days=7 : Cuantos dias hacia atras simular} {--fresh : Borrar datos de demo previos antes de generar}')]
#[Description('Genera areas, dispositivos y lecturas de consumo simuladas para probar el sistema sin hardware real')]
class SeedDemoData extends Command
{
    private const DEVICE_PROFILES = [
        ['area' => 'Oficina', 'name' => 'Aire acondicionado', 'type' => 'Clima', 'model' => 'Mini Split 1 Ton', 'base_watts' => 900, 'noise' => 80],
        ['area' => 'Oficina', 'name' => 'Iluminación general', 'type' => 'Iluminación', 'model' => 'LED Panel 40W', 'base_watts' => 150, 'noise' => 15],
        ['area' => 'Producción', 'name' => 'Compresor principal', 'type' => 'Maquinaria', 'model' => 'Compresor 5HP', 'base_watts' => 3200, 'noise' => 250],
        ['area' => 'Producción', 'name' => 'Banda transportadora', 'type' => 'Maquinaria', 'model' => 'Motor trifásico 2HP', 'base_watts' => 1400, 'noise' => 120],
        ['area' => 'Bodega', 'name' => 'Refrigerador industrial', 'type' => 'Refrigeración', 'model' => 'Walk-in Cooler', 'base_watts' => 1100, 'noise' => 60],
        ['area' => 'Bodega', 'name' => 'Iluminación bodega', 'type' => 'Iluminación', 'model' => 'LED Industrial 100W', 'base_watts' => 300, 'noise' => 20],
    ];

    public function handle(ConsumptionIngestionService $ingestionService): int
    {
        if ($this->option('fresh')) {
            $this->warn('Eliminando lecturas, anomalías y resúmenes de dispositivos demo previos...');
            $deviceNames = collect(self::DEVICE_PROFILES)->pluck('name');
            Device::query()->whereIn('name', $deviceNames)->get()->each(function (Device $device): void {
                $device->consumptionReadings()->delete();
                $device->anomalies()->delete();
                $device->dailyConsumptionSummaries()->delete();
                $device->delete();
            });
        }

        $devices = collect(self::DEVICE_PROFILES)->map(function (array $profile): array {
            $area = Area::firstOrCreate(['name' => $profile['area']]);
            $type = DeviceType::firstOrCreate(['name' => $profile['type']]);
            $model = DeviceModel::firstOrCreate(['name' => $profile['model']]);

            $device = Device::firstOrCreate(
                ['name' => $profile['name'], 'area_id' => $area->id],
                ['device_type_id' => $type->id, 'device_model_id' => $model->id, 'status' => 'on']
            );

            return ['device' => $device, 'profile' => $profile];
        });

        Schedule::firstOrCreate(
            ['name' => 'Apagado nocturno general', 'scope' => 'company'],
            [
                'start_time' => '22:00:00',
                'end_time' => '06:00:00',
                'weekdays' => '0,1,2,3,4,5,6',
                'is_active' => true,
            ]
        );

        $days = (int) $this->option('days');
        $start = Carbon::now()->subDays($days)->startOfDay();
        $end = Carbon::now();

        $totalReadings = 0;
        $totalAnomalies = 0;

        $cursor = $start->copy();
        while ($cursor->lessThan($end)) {
            foreach ($devices as $entry) {
                /** @var Device $device */
                $device = $entry['device'];
                $profile = $entry['profile'];

                $isNight = $cursor->hour >= 22 || $cursor->hour < 6;
                if ($isNight) {
                    $value = max(0, $profile['base_watts'] * 0.05 + $this->jitter($profile['noise'] * 0.2));
                } else {
                    $value = $profile['base_watts'] + $this->jitter($profile['noise']);
                }

                // Inyecta picos anómalos ocasionales (~1.5% de las lecturas diurnas) para probar la detección.
                if (! $isNight && random_int(1, 1000) <= 15) {
                    $value *= random_int(3, 5);
                }

                $result = $ingestionService->ingest($device, round($value, 2), $cursor->copy());
                $totalReadings++;

                if ($result['anomaly']) {
                    $totalAnomalies++;
                }
            }

            $cursor->addMinutes(30);
        }

        $this->info("Generados {$totalReadings} lecturas y {$totalAnomalies} anomalías para ".count(self::DEVICE_PROFILES).' dispositivos.');

        for ($i = $days; $i >= 1; $i--) {
            $this->call('app:summarize-daily-consumption', ['--date' => Carbon::now()->subDays($i)->toDateString()]);
        }

        $this->info('Resúmenes diarios calculados.');

        return self::SUCCESS;
    }

    private function jitter(float $noise): float
    {
        return random_int((int) (-$noise * 100), (int) ($noise * 100)) / 100;
    }
}
