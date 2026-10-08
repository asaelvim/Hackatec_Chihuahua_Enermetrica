<?php

use App\Models\Area;
use App\Models\Device;
use App\Models\DeviceModel;
use App\Models\DeviceType;
use Illuminate\Validation\Rule;
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

    public ?int $controller_device_id = null;

    public ?int $relay_channel = null;

    public function with(): array
    {
        return [
            'devices' => Device::with(['area', 'deviceType', 'deviceModel', 'controller'])->orderBy('name')->paginate(10),
            'areas' => Area::orderBy('name')->get(),
            'deviceTypes' => DeviceType::orderBy('name')->get(),
            'deviceModels' => DeviceModel::orderBy('name')->get(),
            // Cualquier otro dispositivo puede actuar como "controlador"
            // (normalmente el ESP32); se excluye el que se esta editando
            // para no permitir que se controle a si mismo.
            'posiblesControladores' => Device::where('id', '!=', $this->editingId ?? 0)->orderBy('name')->get(),
        ];
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'area_id', 'device_type_id', 'device_model_id', 'controller_device_id', 'relay_channel']);
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
        $this->controller_device_id = $device->controller_device_id;
        $this->relay_channel = $device->relay_channel;
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
            'controller_device_id' => ['nullable', 'exists:devices,id', 'different:editingId'],
            'relay_channel' => [
                'nullable',
                'required_with:controller_device_id',
                'integer',
                'between:1,5',
                Rule::unique('devices', 'relay_channel')
                    ->where('controller_device_id', $this->controller_device_id)
                    ->ignore($this->editingId),
            ],
        ]);

        $previousStatus = $this->editingId ? Device::find($this->editingId)?->status : null;

        $device = Device::updateOrCreate(['id' => $this->editingId], $data);

        if ($previousStatus === 'on' && $device->status !== 'on') {
            $device->turnOffControlledRelayDevices(auth()->user());
        }

        $this->showModal = false;
        $this->dispatch('close');
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
        $this->dispatch('close');
        session()->flash('status', 'Dispositivo eliminado correctamente.');
    }

    /**
     * Encendido/apagado con un clic desde la lista. Solo aplica cuando el
     * estado actual es "on"/"off"; "offline"/"maintenance" se cambian
     * forzosamente solo desde Editar. Si el dispositivo esta controlado
     * por un relevador, el ESP32 recoge este cambio en su siguiente
     * consulta (polling) y despues confirma lo que realmente aplico.
     */
    public function toggleStatus(int $deviceId): void
    {
        Device::findOrFail($deviceId)->toggleStatus(auth()->user());
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Dispositivos</h2>
            <button type="button" wire:click="create" x-data="" x-on:click="$dispatch('open-modal', 'device-form')"
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
                            <td class="px-6 py-4 text-sm">
                                @if ($device->isTogglable())
                                    <button wire:click="toggleStatus({{ $device->id }})" title="Clic para {{ $device->status === 'on' ? 'apagar' : 'encender' }}" class="cursor-pointer">
                                        <x-status-badge :status="$device->status" />
                                    </button>
                                @else
                                    <x-status-badge :status="$device->status" />
                                @endif
                                @if ($device->isRelayControlled())
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ $device->controller?->name }} · Canal {{ $device->relay_channel }}
                                        @if ($device->reported_status && $device->reported_status !== $device->status)
                                            · aplicando…
                                        @endif
                                    </p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm space-x-3 whitespace-nowrap">
                                <button wire:click="edit({{ $device->id }})" x-data="" x-on:click="$dispatch('open-modal', 'device-form')" class="text-blue-600 hover:text-blue-800">Editar</button>
                                <button wire:click="confirmDelete({{ $device->id }})" x-data="" x-on:click="$dispatch('open-modal', 'device-delete')" class="text-red-600 hover:text-red-800">Eliminar</button>
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

            <div class="border-t border-gray-100 pt-4 space-y-4">
                <p class="text-xs text-gray-500">
                    Si este dispositivo se enciende/apaga mediante uno de los 5 relevadores de un ESP32, indica cuál y qué canal. Déjalo vacío si no se controla remotamente (p.ej. el propio ESP32).
                </p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="controller_device_id" value="Controlado por" />
                        <select wire:model="controller_device_id" id="controller_device_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">Ninguno</option>
                            @foreach ($posiblesControladores as $controlador)
                                <option value="{{ $controlador->id }}">{{ $controlador->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('controller_device_id')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="relay_channel" value="Canal (1-5)" />
                        <select wire:model="relay_channel" id="relay_channel" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">—</option>
                            @for ($canal = 1; $canal <= 5; $canal++)
                                <option value="{{ $canal }}">{{ $canal }}</option>
                            @endfor
                        </select>
                        <x-input-error :messages="$errors->get('relay_channel')" class="mt-1" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-secondary-button type="button" wire:click="$set('showModal', false)" x-data="" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
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
                <x-secondary-button type="button" wire:click="$set('confirmingDeleteId', null)" x-data="" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-danger-button wire:click="delete">Eliminar</x-danger-button>
            </div>
        </div>
    </x-modal>
</div>

