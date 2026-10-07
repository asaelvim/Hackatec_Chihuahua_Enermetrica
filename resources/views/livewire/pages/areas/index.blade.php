<?php

use App\Models\Area;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
class extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public ?int $confirmingDeleteId = null;

    public function with(): array
    {
        return [
            'areas' => Area::withCount('devices')->orderBy('name')->paginate(10),
        ];
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name']);
        $this->showModal = true;
    }

    public function edit(int $areaId): void
    {
        $area = Area::findOrFail($areaId);
        $this->editingId = $area->id;
        $this->name = $area->name;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255', 'unique:areas,name,'.$this->editingId],
        ]);

        Area::updateOrCreate(['id' => $this->editingId], $data);

        $this->showModal = false;
        $this->reset(['editingId', 'name']);
        session()->flash('status', 'Área guardada correctamente.');
    }

    public function confirmDelete(int $areaId): void
    {
        $this->confirmingDeleteId = $areaId;
    }

    public function delete(): void
    {
        Area::findOrFail($this->confirmingDeleteId)->delete();
        $this->confirmingDeleteId = null;
        session()->flash('status', 'Área eliminada correctamente.');
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Áreas</h2>
            <button type="button" wire:click="create"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 transition">
                + Agregar área
            </button>
        </div>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @if (session('status'))
            <div class="rounded-md bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        <x-card class="!p-0 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dispositivos</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($areas as $area)
                        <tr wire:key="area-{{ $area->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $area->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $area->devices_count }}</td>
                            <td class="px-6 py-4 text-right text-sm space-x-3">
                                <button wire:click="edit({{ $area->id }})" class="text-blue-600 hover:text-blue-800">Editar</button>
                                <button wire:click="confirmDelete({{ $area->id }})" class="text-red-600 hover:text-red-800">Eliminar</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-400">No hay áreas registradas todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>

        {{ $areas->links() }}
    </div>

    <!-- Modal crear/editar -->
    <x-modal name="area-form" :show="$showModal" maxWidth="md" focusable>
        <form wire:submit="save" class="p-6 space-y-4">
            <h3 class="text-lg font-medium text-gray-900">
                {{ $editingId ? 'Editar área' : 'Nueva área' }}
            </h3>

            <div>
                <x-input-label for="name" value="Nombre" />
                <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-secondary-button type="button" wire:click="$set('showModal', false)">Cancelar</x-secondary-button>
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-500">
                    Guardar
                </button>
            </div>
        </form>
    </x-modal>

    <!-- Modal confirmar eliminación -->
    <x-modal name="area-delete" :show="$confirmingDeleteId !== null" maxWidth="sm" focusable>
        <div class="p-6 space-y-4">
            <h3 class="text-lg font-medium text-gray-900">¿Eliminar esta área?</h3>
            <p class="text-sm text-gray-500">
                Esta acción no se puede deshacer. Los dispositivos asociados también se eliminarán.
            </p>
            <div class="flex justify-end gap-3">
                <x-secondary-button type="button" wire:click="$set('confirmingDeleteId', null)">Cancelar</x-secondary-button>
                <x-danger-button wire:click="delete">Eliminar</x-danger-button>
            </div>
        </div>
    </x-modal>
</div>
