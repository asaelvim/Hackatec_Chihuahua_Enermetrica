<?php

use App\Models\Anomaly;
use App\Models\ConsumptionReading;
use App\Models\Device;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public function with(): array
    {
        return [
            'totalDevices' => Device::count(),
            'onDevices' => Device::where('status', 'on')->count(),
            'offlineDevices' => Device::where('status', 'offline')->count(),
            'maintenanceDevices' => Device::where('status', 'maintenance')->count(),
            'pendingAnomalies' => Anomaly::whereNull('reviewed_at')->count(),
            'chartData' => $this->consumptionChartData(),
        ];
    }

    private function consumptionChartData(): array
    {
        $start = Carbon::now()->subHours(23)->startOfHour();

        $readings = ConsumptionReading::query()
            ->selectRaw('DATE_FORMAT(read_at, "%Y-%m-%d %H:00:00") as bucket, SUM(value) as total')
            ->where('read_at', '>=', $start)
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $labels = [];
        $data = [];

        for ($i = 0; $i < 24; $i++) {
            $hour = $start->copy()->addHours($i);
            $labels[] = $hour->format('H:i');
            $data[] = (float) ($readings[$hour->format('Y-m-d H:00:00')] ?? 0);
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Consumo total (W)',
                'data' => $data,
                'borderColor' => '#2563eb',
                'backgroundColor' => 'rgba(37, 99, 235, 0.15)',
                'fill' => true,
                'tension' => 0.3,
                'pointRadius' => 0,
            ]],
        ];
    }

    public function poll(): void
    {
        $this->dispatch('dashboard-chart-updated', data: $this->consumptionChartData());
    }
}; ?>

<div wire:poll.5s="poll">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Dashboard</h2>
    </x-slot>

    <div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <x-card>
                <p class="text-sm text-gray-500">Dispositivos</p>
                <p class="text-2xl font-semibold text-gray-800 mt-1">{{ $totalDevices }}</p>
            </x-card>
            <x-card>
                <p class="text-sm text-gray-500">Encendidos</p>
                <p class="text-2xl font-semibold text-emerald-600 mt-1">{{ $onDevices }}</p>
            </x-card>
            <x-card>
                <p class="text-sm text-gray-500">Sin conexión</p>
                <p class="text-2xl font-semibold text-red-600 mt-1">{{ $offlineDevices }}</p>
            </x-card>
            <x-card>
                <p class="text-sm text-gray-500">En mantenimiento</p>
                <p class="text-2xl font-semibold text-amber-600 mt-1">{{ $maintenanceDevices }}</p>
            </x-card>
            <x-card>
                <p class="text-sm text-gray-500">Anomalías pendientes</p>
                <p class="text-2xl font-semibold text-amber-600 mt-1">{{ $pendingAnomalies }}</p>
            </x-card>
        </div>

        <x-card>
            <h3 class="text-sm font-medium text-gray-700 mb-3">Consumo total — últimas 24 horas</h3>
            <div wire:ignore x-data="initLineChart(@js($chartData), 'dashboard-chart-updated')" class="h-64">
                <canvas x-ref="canvas"></canvas>
            </div>
        </x-card>

        <x-card>
            <h3 class="text-sm font-medium text-gray-700 mb-3">Accesos rápidos</h3>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('web.areas.index') }}" wire:navigate class="px-4 py-2 text-sm rounded-md bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200">Áreas</a>
                <a href="{{ route('web.devices.index') }}" wire:navigate class="px-4 py-2 text-sm rounded-md bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200">Dispositivos</a>
                <a href="{{ route('web.schedules.index') }}" wire:navigate class="px-4 py-2 text-sm rounded-md bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200">Horarios</a>
                <a href="{{ route('web.anomalies.index') }}" wire:navigate class="px-4 py-2 text-sm rounded-md bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200">Anomalías</a>
                <a href="{{ route('web.statistics.index') }}" wire:navigate class="px-4 py-2 text-sm rounded-md bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200">Estadísticas</a>
            </div>
        </x-card>
    </div>
</div>
