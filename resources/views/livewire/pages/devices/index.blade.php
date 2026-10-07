<?php

use App\Models\Area;
use App\Models\Device;
use App\Models\DeviceModel;
use App\Models\DeviceType;
use Illuminate\Support\Str;
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

    public ?int $area_id = null;

    public ?int $device_type_id = null;

    public ?int $device_model_id = null;

    public string $status = 'off';

    public ?int $confirmingDeleteId = null;

    public ?string $generatedToken = null;

    public function with(): array
    {
        return [
            'devices' => Device::with(['area', 'deviceType', 'deviceModel'])->orderBy('name')->paginate(10),
            'areas' => Area::orderBy('name')->get(),
            'deviceTypes' => DeviceType::orderBy('name')->get(),
            'deviceModels' => DeviceModel::orderBy('name')->get(),
        ];
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'area_id', 'device_type_id', 'device_model_id']);
        $this->status = 'off';
        $this->showModal = true;
    }

    public function edit(int $deviceId): void
    {
        $device = Device::findOrFail($deviceId);
        $this->editingId = $device->id;
        $this->name = $device->name;
        $this->area_id = $device->area_id;
        $this->device_type_id = $device->device_type_id;
        $this->device_model_id = $device->device_model_id;
        $this->status = $device->status;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'area_id' => ['required', 'exists:areas,id'],
            'device_type_id' => ['nullable', 'exists:device_types,id'],
            'device_model_id' => ['nullable', 'exists:device_models,id'],
            'status' => ['required', 'in:on,off,offline,maintenance'],
        ]);

        Device::updateOrCreate(['id' => $this->editingId], $data);

        $this->showModal = false;
        session()->flash('status', 'Dispositivo guardado correctamente.');
    }

    public function confirmDelete(int $deviceId): void
    {
        $this->confirmingDeleteId = $deviceId;
    }

    public function delete(): void
    {
        Device::findOrFail($this->confirmingDeleteId)->delete();
        $this->confirmingDeleteId = null;
        session()->flash('status', 'Dispositivo eliminado correctamente.');
    }

    public function regenerateToken(int $deviceId): void
    {
        $device = Device::findOrFail($deviceId);
        $device->forceFill(['api_token' => Str::random(40)])->save();
        $this->generatedToken = $device->api_token;
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Dispositivos</h2>
            <button type="button" wire:click="create"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 transition">
                + Agregar dispositivo
            </button>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Área</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($devices as $device)
                        <tr wire:key="device-{{ $device->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $device->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $device->area?->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $device->deviceType?->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm"><x-status-badge :status="$device->status" /></td>
                            <td class="px-6 py-4 text-right text-sm space-x-3 whitespace-nowrap">
                                <button wire:click="regenerateToken({{ $device->id }})" class="text-gray-500 hover:text-gray-700">Regenerar token</button>
                                <button wire:click="edit({{ $device->id }})" class="text-blue-600 hover:text-blue-800">Editar</button>
                                <button wire:click="confirmDelete({{ $device->id }})" class="text-red-600 hover:text-red-800">Eliminar</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-400">No hay dispositivos registrados todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>

        {{ $devices->links() }}
    </div>

    <!-- Modal crear/editar -->
    <x-modal name="device-form" :show="$showModal" maxWidth="md" focusable>
        <form wire:submit="save" class="p-6 space-y-4">
            <h3 class="text-lg font-medium text-gray-900">{{ $editingId ? 'Editar dispositivo' : 'Nuevo dispositivo' }}</h3>

            <div>
                <x-input-label for="name" value="Nombre" />
                <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="area_id" value="Área" />
                <select wire:model="area_id" id="area_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">Selecciona un área</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area->id }}">{{ $area->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('area_id')" class="mt-1" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="device_type_id" value="Tipo" />
                    <select wire:model="device_type_id" id="device_type_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">Sin tipo</option>
                        @foreach ($deviceTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="device_model_id" value="Modelo" />
                    <select wire:model="device_model_id" id="device_model_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">Sin modelo</option>
                        @foreach ($deviceModels as $model)
                            <option value="{{ $model->id }}">{{ $model->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <x-input-label for="status" value="Estado" />
                <select wire:model="status" id="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="on">Encendido</option>
                    <option value="off">Apagado</option>
                    <option value="offline">Sin conexión</option>
                    <option value="maintenance">Mantenimiento</option>
                </select>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-secondary-button type="button" wire:click="$set('showModal', false)">Cancelar</x-secondary-button>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-500">
                    Guardar
                </button>
            </div>
        </form>
    </x-modal>

    <!-- Modal confirmar eliminación -->
    <x-modal name="device-delete" :show="$confirmingDeleteId !== null" maxWidth="sm" focusable>
        <div class="p-6 space-y-4">
            <h3 class="text-lg font-medium text-gray-900">¿Eliminar este dispositivo?</h3>
            <p class="text-sm text-gray-500">Esta acción no se puede deshacer.</p>
            <div class="flex justify-end gap-3">
                <x-secondary-button type="button" wire:click="$set('confirmingDeleteId', null)">Cancelar</x-secondary-button>
                <x-danger-button wire:click="delete">Eliminar</x-danger-button>
            </div>
        </div>
    </x-modal>

    <!-- Modal token regenerado -->
    <x-modal name="device-token" :show="$generatedToken !== null" maxWidth="md" focusable>
        <div class="p-6 space-y-4">
            <h3 class="text-lg font-medium text-gray-900">Nuevo token del dispositivo</h3>
            <p class="text-sm text-gray-500">
                Cópialo ahora: por seguridad no se volverá a mostrar. Configúralo en el sensor para que siga enviando lecturas.
            </p>
            <code class="block w-full break-all bg-gray-50 border border-gray-200 rounded-md px-3 py-2 text-sm text-gray-800">
                {{ $generatedToken }}
            </code>
            <div class="flex justify-end">
                <x-secondary-button type="button" wire:click="$set('generatedToken', null)">Cerrar</x-secondary-button>
            </div>
        </div>
    </x-modal>
</div>
