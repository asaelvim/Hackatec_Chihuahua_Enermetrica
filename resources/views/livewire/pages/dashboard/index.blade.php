<?php

use App\Models\Anomaly;
use App\Models\Area;
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
            'topDevices' => $this->topDevices(),
            'recentAnomalies' => Anomaly::with('device')->latest('id')->limit(5)->get(),
            'areaBreakdown' => Area::withCount('devices')->orderByDesc('devices_count')->get(),
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
            $data[] = round((float) ($readings[$hour->format('Y-m-d H:00:00')] ?? 0) / 1000, 3);
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Consumo total (kW)',
                'data' => $data,
                'borderColor' => '#2563eb',
                'backgroundColor' => 'rgba(37, 99, 235, 0.15)',
                'fill' => true,
                'tension' => 0.3,
                'pointRadius' => 0,
            ]],
        ];
    }

    private function topDevices()
    {
        $start = Carbon::now()->subHours(24);

        return ConsumptionReading::query()
            ->with('device.area')
            ->selectRaw('device_id, SUM(value) as total, AVG(value) as average')
            ->where('read_at', '>=', $start)
            ->groupBy('device_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
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
            <x-card class="flex flex-col gap-2">
                <div class="flex items-center gap-3">
                    <span class="flex-shrink-0 rounded-lg bg-indigo-50 p-2 text-indigo-600">
                        <i class="fa-solid fa-microchip text-lg"></i>
                    </span>
                    <div>
                        <p class="text-sm text-gray-500">Dispositivos</p>
                        <p class="text-2xl font-semibold text-gray-800 mt-1">{{ $totalDevices }}</p>
                    </div>
                </div>
                <a href="{{ route('web.devices.index') }}" wire:navigate class="text-xs text-indigo-600 hover:text-indigo-800">Ver más →</a>
            </x-card>
            <x-card class="flex flex-col gap-2">
                <div class="flex items-center gap-3">
                    <span class="flex-shrink-0 rounded-lg bg-emerald-50 p-2 text-emerald-600">
                        <i class="fa-solid fa-bolt text-lg"></i>
                    </span>
                    <div>
                        <p class="text-sm text-gray-500">Encendidos</p>
                        <p class="text-2xl font-semibold text-emerald-600 mt-1">{{ $onDevices }}</p>
                    </div>
                </div>
                <a href="{{ route('web.devices.index') }}" wire:navigate class="text-xs text-indigo-600 hover:text-indigo-800">Ver más →</a>
            </x-card>
            <x-card class="flex flex-col gap-2">
                <div class="flex items-center gap-3">
                    <span class="flex-shrink-0 rounded-lg bg-red-50 p-2 text-red-600">
                        <i class="fa-solid fa-ban text-lg"></i>
                    </span>
                    <div>
                        <p class="text-sm text-gray-500">Sin conexión</p>
                        <p class="text-2xl font-semibold text-red-600 mt-1">{{ $offlineDevices }}</p>
                    </div>
                </div>
                <a href="{{ route('web.devices.index') }}" wire:navigate class="text-xs text-indigo-600 hover:text-indigo-800">Ver más →</a>
            </x-card>
            <x-card class="flex flex-col gap-2">
                <div class="flex items-center gap-3">
                    <span class="flex-shrink-0 rounded-lg bg-amber-50 p-2 text-amber-600">
                        <i class="fa-solid fa-screwdriver-wrench text-lg"></i>
                    </span>
                    <div>
                        <p class="text-sm text-gray-500">En mantenimiento</p>
                        <p class="text-2xl font-semibold text-amber-600 mt-1">{{ $maintenanceDevices }}</p>
                    </div>
                </div>
                <a href="{{ route('web.devices.index') }}" wire:navigate class="text-xs text-indigo-600 hover:text-indigo-800">Ver más →</a>
            </x-card>
            <x-card class="flex flex-col gap-2">
                <div class="flex items-center gap-3">
                    <span class="flex-shrink-0 rounded-lg bg-amber-50 p-2 text-amber-600">
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    </span>
                    <div>
                        <p class="text-sm text-gray-500">Anomalías pendientes</p>
                        <p class="text-2xl font-semibold text-amber-600 mt-1">{{ $pendingAnomalies }}</p>
                    </div>
                </div>
                <a href="{{ route('web.anomalies.index') }}" wire:navigate class="text-xs text-indigo-600 hover:text-indigo-800">Ver más →</a>
            </x-card>
        </div>

        <x-card>
            <div class="flex items-center justify-between mb-3">
                <h3 class="flex items-center gap-2 text-sm font-medium text-gray-700">
                    <i class="fa-solid fa-chart-line text-indigo-600"></i>
                    Consumo total — últimas 24 horas
                </h3>
                <a href="{{ route('web.statistics.index') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-800">Ver más →</a>
            </div>
            <div wire:ignore x-data="initLineChart(@js($chartData), 'dashboard-chart-updated')" class="h-64">
                <canvas x-ref="canvas"></canvas>
            </div>
        </x-card>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <x-card class="lg:col-span-2">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="flex items-center gap-2 text-sm font-medium text-gray-700">
                        <i class="fa-solid fa-trophy text-indigo-600"></i>
                        Top 5 dispositivos — consumo últimas 24h
                    </h3>
                    <a href="{{ route('web.devices.index') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-800">Ver más →</a>
                </div>
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead>
                        <tr>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dispositivo</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Área</th>
                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total (kW)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($topDevices as $reading)
                            <tr wire:key="top-device-{{ $reading->device_id }}">
                                <td class="px-2 py-2 text-sm font-medium text-gray-800">{{ $reading->device?->name ?? '—' }}</td>
                                <td class="px-2 py-2 text-sm text-gray-500">{{ $reading->device?->area?->name ?? '—' }}</td>
                                <td class="px-2 py-2 text-sm text-gray-500 text-right whitespace-nowrap">{{ number_format((float) $reading->total / 1000, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-2 py-6 text-center text-sm text-gray-400">Sin lecturas en las últimas 24 horas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </x-card>

            <x-card>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="flex items-center gap-2 text-sm font-medium text-gray-700">
                        <i class="fa-solid fa-building text-indigo-600"></i>
                        Dispositivos por área
                    </h3>
                    <a href="{{ route('web.areas.index') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-800">Ver más →</a>
                </div>
                <ul class="divide-y divide-gray-100">
                    @forelse ($areaBreakdown as $area)
                        <li wire:key="area-{{ $area->id }}" class="flex items-center justify-between py-2 text-sm">
                            <span class="text-gray-700">{{ $area->name }}</span>
                            <span class="font-semibold text-gray-800">{{ $area->devices_count }}</span>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-gray-400">Sin áreas registradas.</li>
                    @endforelse
                </ul>
            </x-card>
        </div>

        <x-card>
            <div class="flex items-center justify-between mb-3">
                <h3 class="flex items-center gap-2 text-sm font-medium text-gray-700">
                    <i class="fa-solid fa-clock text-indigo-600"></i>
                    Anomalías recientes
                </h3>
                <a href="{{ route('web.anomalies.index') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-800">Ver más →</a>
            </div>
            <table class="min-w-full divide-y divide-gray-100">
                <thead>
                    <tr>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dispositivo</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Z-score</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($recentAnomalies as $anomaly)
                        <tr wire:key="recent-anomaly-{{ $anomaly->id }}">
                            <td class="px-2 py-2 text-sm font-medium text-gray-800">{{ $anomaly->device?->name ?? '—' }}</td>
                            <td class="px-2 py-2 text-sm text-gray-500">{{ number_format((float) $anomaly->value / 1000, 2) }} kW</td>
                            <td class="px-2 py-2 text-sm text-gray-500">{{ number_format((float) $anomaly->z_score, 2) }}</td>
                            <td class="px-2 py-2 text-sm text-gray-500">{{ $anomaly->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-2 py-2 text-sm">
                                @if ($anomaly->reviewed_at)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-700">
                                        <i class="fa-solid fa-circle-check"></i>
                                        Revisada
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-700">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        Pendiente
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-2 py-6 text-center text-sm text-gray-400">No hay anomalías registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>
    </div>
</div>
