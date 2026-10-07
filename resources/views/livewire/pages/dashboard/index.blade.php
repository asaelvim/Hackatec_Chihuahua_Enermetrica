<?php

use App\Models\Anomaly;
use App\Models\Device;
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
        ];
    }
}; ?>

<div>
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
