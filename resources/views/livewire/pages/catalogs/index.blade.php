<?php

use App\Models\DeviceModel;
use App\Models\DeviceType;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public string $tab = 'types';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public ?int $confirmingDeleteId = null;

    public function with(): array
    {
        return [
            'deviceTypes' => DeviceType::withCount('devices')->orderBy('name')->get(),
            'deviceModels' => DeviceModel::withCount('devices')->orderBy('name')->get(),
        ];
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name']);
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $model = $this->tab === 'types' ? DeviceType::findOrFail($id) : DeviceModel::findOrFail($id);
        $this->editingId = $model->id;
        $this->name = $model->name;
        $this->showModal = true;
    }

    public function save(): void
    {
        $table = $this->tab === 'types' ? 'device_types' : 'device_models';

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255', "unique:{$table},name,{$this->editingId}"],
        ]);

        if ($this->tab === 'types') {
            DeviceType::updateOrCreate(['id' => $this->editingId], $data);
        } else {
            DeviceModel::updateOrCreate(['id' => $this->editingId], $data);
        }

        $this->showModal = false;
        $this->reset(['editingId', 'name']);
        $this->dispatch('close');
        session()->flash('status', 'Guardado correctamente.');
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
    }

    public function delete(): void
    {
        if ($this->tab === 'types') {
            DeviceType::findOrFail($this->confirmingDeleteId)->delete();
        } else {
            DeviceModel::findOrFail($this->confirmingDeleteId)->delete();
        }

        $this->confirmingDeleteId = null;
        $this->dispatch('close');
        session()->flash('status', 'Eliminado correctamente.');
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Catálogos</h2>
            <button type="button" wire:click="create" x-data="" x-on:click="$dispatch('open-modal', 'catalog-form')"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 transition">
                + Agregar
            </button>
        </div>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @if (session('status'))
            <div class="rounded-md bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex gap-2 border-b border-gray-200">
            <button wire:click="setTab('types')"
                class="px-4 py-2 text-sm font-medium border-b-2 -mb-px {{ $tab === 'types' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Tipos de dispositivo
            </button>
            <button wire:click="setTab('models')"
                class="px-4 py-2 text-sm font-medium border-b-2 -mb-px {{ $tab === 'models' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Modelos de dispositivo
            </button>
        </div>

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
                    @forelse (($tab === 'types' ? $deviceTypes : $deviceModels) as $item)
                        <tr wire:key="catalog-{{ $tab }}-{{ $item->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $item->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $item->devices_count }}</td>
                            <td class="px-6 py-4 text-right text-sm space-x-3">
                                <button wire:click="edit({{ $item->id }})" x-data="" x-on:click="$dispatch('open-modal', 'catalog-form')" class="text-blue-600 hover:text-blue-800">Editar</button>
                                <button wire:click="confirmDelete({{ $item->id }})" x-data="" x-on:click="$dispatch('open-modal', 'catalog-delete')" class="text-red-600 hover:text-red-800">Eliminar</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-400">No hay registros todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>
    </div>

    <x-modal name="catalog-form" :show="$showModal" maxWidth="md" focusable>
        <form wire:submit="save" class="p-6 space-y-4">
            <h3 class="text-lg font-medium text-gray-900">
                {{ $editingId ? 'Editar' : 'Nuevo' }} {{ $tab === 'types' ? 'tipo de dispositivo' : 'modelo de dispositivo' }}
            </h3>

            <div>
                <x-input-label for="name" value="Nombre" />
                <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-secondary-button type="button" wire:click="$set('showModal', false)" x-data="" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-500">
                    Guardar
                </button>
            </div>
        </form>
    </x-modal>

    <x-modal name="catalog-delete" :show="$confirmingDeleteId !== null" maxWidth="sm" focusable>
        <div class="p-6 space-y-4">
            <h3 class="text-lg font-medium text-gray-900">¿Eliminar este registro?</h3>
            <p class="text-sm text-gray-500">Esta acción no se puede deshacer.</p>
            <div class="flex justify-end gap-3">
                <x-secondary-button type="button" wire:click="$set('confirmingDeleteId', null)" x-data="" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-danger-button wire:click="delete">Eliminar</x-danger-button>
            </div>
        </div>
    </x-modal>
</div>
