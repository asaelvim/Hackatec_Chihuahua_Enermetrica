<?php

use App\Models\Anomaly;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
class extends Component
{
    use WithPagination;

    public bool $onlyUnreviewed = false;

    public function with(): array
    {
        return [
            'anomalies' => Anomaly::with(['device', 'consumptionReading'])
                ->when($this->onlyUnreviewed, fn ($query) => $query->whereNull('reviewed_at'))
                ->latest('created_at')
                ->paginate(10),
        ];
    }

    public function markReviewed(int $anomalyId): void
    {
        Anomaly::findOrFail($anomalyId)->update(['reviewed_at' => now()]);
        session()->flash('status', 'Anomalía marcada como revisada.');
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Anomalías</h2>
            <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" wire:model.live="onlyUnreviewed" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                Mostrar solo no revisadas
            </label>
        </div>
    </x-slot>

    <div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @if (session('status'))
            <div class="rounded-md bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        <x-card class="!p-0 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dispositivo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Z-score</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($anomalies as $anomaly)
                        <tr wire:key="anomaly-{{ $anomaly->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $anomaly->device?->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ number_format((float) $anomaly->value, 2) }} W</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ number_format((float) $anomaly->z_score, 2) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $anomaly->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 text-sm">
                                @if ($anomaly->reviewed_at)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Revisada</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Pendiente</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                @unless ($anomaly->reviewed_at)
                                    <button wire:click="markReviewed({{ $anomaly->id }})" class="text-blue-600 hover:text-blue-800">Marcar como revisada</button>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">No hay anomalías registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>

        {{ $anomalies->links() }}
    </div>
</div>
