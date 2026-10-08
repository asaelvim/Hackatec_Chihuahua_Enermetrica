<?php

use App\Models\DailyConsumptionSummary;
use App\Models\Device;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
class extends Component
{
    use WithPagination;

    public ?int $device_id = null;

    public string $from = '';

    public string $to = '';

    public function mount(): void
    {
        $this->from = Carbon::now()->subDays(6)->toDateString();
        $this->to = Carbon::now()->toDateString();
    }

    public function updated(): void
    {
        $this->resetPage();
        $this->dispatch('statistics-chart-updated', data: $this->chartData());
    }

    public function poll(): void
    {
        $this->dispatch('statistics-chart-updated', data: $this->chartData());
    }

    public function with(): array
    {
        $summaries = DailyConsumptionSummary::with('device')
            ->when($this->device_id, fn ($query) => $query->where('device_id', $this->device_id))
            ->when($this->from, fn ($query) => $query->where('date', '>=', $this->from))
            ->when($this->to, fn ($query) => $query->where('date', '<=', $this->to))
            ->orderByDesc('date')
            ->paginate(10);

        $totals = DailyConsumptionSummary::query()
            ->when($this->device_id, fn ($query) => $query->where('device_id', $this->device_id))
            ->when($this->from, fn ($query) => $query->where('date', '>=', $this->from))
            ->when($this->to, fn ($query) => $query->where('date', '<=', $this->to))
            ->selectRaw('SUM(total_kwh) as total_kwh, AVG(avg_watts) as avg_watts, MAX(max_watts) as max_watts')
            ->first();

        $maxDaily = (float) ($summaries->max('total_kwh') ?: 1);

        return [
            'summaries' => $summaries,
            'totals' => $totals,
            'maxDaily' => $maxDaily,
            'devices' => Device::orderBy('name')->get(),
            'chartData' => $this->chartData(),
        ];
    }

    private function chartData(): array
    {
        $rows = DailyConsumptionSummary::query()
            ->when($this->device_id, fn ($query) => $query->where('device_id', $this->device_id))
            ->when($this->from, fn ($query) => $query->where('date', '>=', $this->from))
            ->when($this->to, fn ($query) => $query->where('date', '<=', $this->to))
            ->selectRaw('date, SUM(total_kwh) as total_kwh')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $rows->map(fn ($row) => Carbon::parse($row->date)->format('d/m'))->all(),
            'datasets' => [[
                'label' => 'Consumo diario (kWh)',
                'data' => $rows->map(fn ($row) => (float) $row->total_kwh)->all(),
                'borderColor' => '#2563eb',
                'backgroundColor' => 'rgba(37, 99, 235, 0.15)',
                'fill' => true,
                'tension' => 0.3,
                'pointRadius' => 2,
            ]],
        ];
    }
}; ?>

<div wire:poll.5s="poll">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Estadísticas de consumo</h2>
    </x-slot>

    <div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <x-card>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <x-input-label for="device_id" value="Dispositivo" />
                    <select wire:model.live="device_id" id="device_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">Todos los dispositivos</option>
                        @foreach ($devices as $device)
                            <option value="{{ $device->id }}">{{ $device->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="from" value="Desde" />
                    <x-text-input wire:model.live="from" id="from" type="date" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label for="to" value="Hasta" />
                    <x-text-input wire:model.live="to" id="to" type="date" class="mt-1 block w-full" />
                </div>
            </div>
        </x-card>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-card>
                <p class="text-sm text-gray-500">Consumo total</p>
                <p class="text-2xl font-semibold text-gray-800 mt-1">{{ number_format((float) ($totals->total_kwh ?? 0), 2) }} kWh</p>
            </x-card>
            <x-card>
                <p class="text-sm text-gray-500">Promedio de potencia</p>
                <p class="text-2xl font-semibold text-gray-800 mt-1">{{ number_format((float) ($totals->avg_watts ?? 0), 1) }} W</p>
            </x-card>
            <x-card>
                <p class="text-sm text-gray-500">Pico máximo</p>
                <p class="text-2xl font-semibold text-gray-800 mt-1">{{ number_format((float) ($totals->max_watts ?? 0), 1) }} W</p>
            </x-card>
        </div>

        <x-card>
            <h3 class="text-sm font-medium text-gray-700 mb-3">Consumo diario en el rango seleccionado</h3>
            <div wire:ignore x-data="initLineChart(@js($chartData), 'statistics-chart-updated')" class="h-64">
                <canvas x-ref="canvas"></canvas>
            </div>
        </x-card>

        <x-card class="!p-0 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dispositivo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">kWh del día</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proporción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($summaries as $summary)
                        <tr wire:key="summary-{{ $summary->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $summary->date->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $summary->device?->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ number_format((float) $summary->total_kwh, 3) }}</td>
                            <td class="px-6 py-4 text-sm w-1/3">
                                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-2 bg-blue-500 rounded-full" style="width: {{ min(100, ((float) $summary->total_kwh / $maxDaily) * 100) }}%"></div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-400">No hay datos para el rango seleccionado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>

        {{ $summaries->links() }}
    </div>
</div>
