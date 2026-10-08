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

    /** @var array<int, int> */
    public array $selected = [];

    public bool $selectAll = false;

    public function with(): array
    {
        return [
            'anomalies' => Anomaly::with(['device', 'consumptionReading'])
                ->when($this->onlyUnreviewed, fn ($query) => $query->whereNull('reviewed_at'))
                ->latest('created_at')
                ->paginate(10),
        ];
    }

    public function updatedOnlyUnreviewed(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function toggleSelectAll(): void
    {
        if (! $this->selectAll) {
            $this->selected = [];

            return;
        }

        $this->selected = Anomaly::query()
            ->when($this->onlyUnreviewed, fn ($query) => $query->whereNull('reviewed_at'))
            ->whereNull('reviewed_at')
            ->latest('created_at')
            ->forPage($this->getPage(), 10)
            ->pluck('id')
            ->all();
    }

    public function markReviewed(int $anomalyId): void
    {
        Anomaly::findOrFail($anomalyId)->update(['reviewed_at' => now()]);
        $this->selected = array_values(array_diff($this->selected, [$anomalyId]));
        session()->flash('status', 'Anomalía marcada como revisada.');
    }

    public function markSelectedReviewed(): void
    {
        if (empty($this->selected)) {
            return;
        }

        $count = Anomaly::whereIn('id', $this->selected)
            ->whereNull('reviewed_at')
            ->update(['reviewed_at' => now()]);

        $this->resetSelection();

        session()->flash('status', $count === 1
            ? '1 anomalía marcada como revisada.'
            : "{$count} anomalías marcadas como revisadas.");
    }

    private function resetSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Anomalías</h2>
    </x-slot>

    <div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @if (session('status'))
            <div class="rounded-md bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" wire:model.live="onlyUnreviewed" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                Mostrar solo no revisadas
            </label>

            <button
                type="button"
                wire:click="markSelectedReviewed"
                wire:confirm="¿Marcar {{ count($selected) }} anomalía(s) seleccionada(s) como revisada(s)?"
                @disabled(empty($selected))
                class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                Marcar seleccionadas como revisadas ({{ count($selected) }})
            </button>
        </div>

        <x-card class="!p-0 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" wire:model.live="selectAll" wire:change="toggleSelectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dispositivo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Severidad</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($anomalies as $anomaly)
                        <tr wire:key="anomaly-{{ $anomaly->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm">
                                @unless ($anomaly->reviewed_at)
                                    <input type="checkbox" wire:model.live="selected" value="{{ $anomaly->id }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                @endunless
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $anomaly->device?->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ number_format((float) $anomaly->value / 1000, 2) }} kW</td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <x-severity-badge :score="$anomaly->z_score" />
                            </td>
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
                            <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-400">No hay anomalías registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>

        {{ $anomalies->links() }}
    </div>
</div>
