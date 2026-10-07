<?php

use App\Models\Area;
use App\Models\Device;
use App\Models\Schedule;
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

    public string $scope = 'company';

    public ?int $area_id = null;

    public ?int $device_id = null;

    public string $start_time = '22:00';

    public string $end_time = '';

    /** @var array<int, string> */
    public array $weekdays = ['0', '1', '2', '3', '4', '5', '6'];

    public bool $is_active = true;

    public ?int $confirmingDeleteId = null;

    protected array $dayLabels = [
        '0' => 'Dom', '1' => 'Lun', '2' => 'Mar', '3' => 'Mié', '4' => 'Jue', '5' => 'Vie', '6' => 'Sáb',
    ];

    public function with(): array
    {
        return [
            'schedules' => Schedule::with(['area', 'device'])->orderBy('name')->paginate(10),
            'areas' => Area::orderBy('name')->get(),
            'devices' => Device::orderBy('name')->get(),
            'dayLabels' => $this->dayLabels,
        ];
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'area_id', 'device_id', 'end_time']);
        $this->scope = 'company';
        $this->start_time = '22:00';
        $this->weekdays = ['0', '1', '2', '3', '4', '5', '6'];
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit(int $scheduleId): void
    {
        $schedule = Schedule::findOrFail($scheduleId);
        $this->editingId = $schedule->id;
        $this->name = $schedule->name;
        $this->scope = $schedule->scope;
        $this->area_id = $schedule->area_id;
        $this->device_id = $schedule->device_id;
        $this->start_time = substr($schedule->start_time, 0, 5);
        $this->end_time = $schedule->end_time ? substr($schedule->end_time, 0, 5) : '';
        $this->weekdays = explode(',', $schedule->weekdays);
        $this->is_active = $schedule->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'scope' => ['required', 'in:company,area,device'],
            'area_id' => [$this->scope === 'area' ? 'required' : 'nullable', 'exists:areas,id'],
            'device_id' => [$this->scope === 'device' ? 'required' : 'nullable', 'exists:devices,id'],
            'start_time' => ['required'],
            'end_time' => ['nullable'],
            'weekdays' => ['required', 'array', 'min:1'],
        ]);

        Schedule::updateOrCreate(['id' => $this->editingId], [
            'name' => $this->name,
            'scope' => $this->scope,
            'area_id' => $this->scope === 'area' ? $this->area_id : null,
            'device_id' => $this->scope === 'device' ? $this->device_id : null,
            'start_time' => $this->start_time.':00',
            'end_time' => $this->end_time ? $this->end_time.':00' : null,
            'weekdays' => implode(',', $this->weekdays),
            'is_active' => $this->is_active,
        ]);

        $this->showModal = false;
        session()->flash('status', 'Horario guardado correctamente.');
    }

    public function confirmDelete(int $scheduleId): void
    {
        $this->confirmingDeleteId = $scheduleId;
    }

    public function delete(): void
    {
        Schedule::findOrFail($this->confirmingDeleteId)->delete();
        $this->confirmingDeleteId = null;
        session()->flash('status', 'Horario eliminado correctamente.');
    }

    public function toggleActive(int $scheduleId): void
    {
        $schedule = Schedule::findOrFail($scheduleId);
        $schedule->update(['is_active' => ! $schedule->is_active]);
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Horarios</h2>
            <button type="button" wire:click="create"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 transition">
                + Agregar horario
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alcance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Horario</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activo</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($schedules as $schedule)
                        <tr wire:key="schedule-{{ $schedule->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $schedule->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                @if ($schedule->scope === 'company') Toda la empresa
                                @elseif ($schedule->scope === 'area') Área: {{ $schedule->area?->name }}
                                @else Dispositivo: {{ $schedule->device?->name }}
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ substr($schedule->start_time, 0, 5) }}@if ($schedule->end_time) – {{ substr($schedule->end_time, 0, 5) }}@endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <button wire:click="toggleActive({{ $schedule->id }})"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $schedule->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $schedule->is_active ? 'Sí' : 'No' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right text-sm space-x-3 whitespace-nowrap">
                                <button wire:click="edit({{ $schedule->id }})" class="text-blue-600 hover:text-blue-800">Editar</button>
                                <button wire:click="confirmDelete({{ $schedule->id }})" class="text-red-600 hover:text-red-800">Eliminar</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-400">No hay horarios registrados todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>

        {{ $schedules->links() }}
    </div>

    <x-modal name="schedule-form" :show="$showModal" maxWidth="lg" focusable>
        <form wire:submit="save" class="p-6 space-y-4">
            <h3 class="text-lg font-medium text-gray-900">{{ $editingId ? 'Editar horario' : 'Nuevo horario' }}</h3>

            <div>
                <x-input-label for="name" value="Nombre" />
                <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="scope" value="Alcance" />
                <select wire:model.live="scope" id="scope" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="company">Toda la empresa</option>
                    <option value="area">Un área</option>
                    <option value="device">Un dispositivo</option>
                </select>
            </div>

            @if ($scope === 'area')
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
            @elseif ($scope === 'device')
                <div>
                    <x-input-label for="device_id" value="Dispositivo" />
                    <select wire:model="device_id" id="device_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">Selecciona un dispositivo</option>
                        @foreach ($devices as $device)
                            <option value="{{ $device->id }}">{{ $device->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('device_id')" class="mt-1" />
                </div>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="start_time" value="Hora de inicio" />
                    <x-text-input wire:model="start_time" id="start_time" type="time" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label for="end_time" value="Hora de fin (opcional)" />
                    <x-text-input wire:model="end_time" id="end_time" type="time" class="mt-1 block w-full" />
                </div>
            </div>
            <p class="text-xs text-gray-400 -mt-2">
                Si la hora de fin es menor a la de inicio, se interpreta como un horario nocturno (cruza la medianoche).
            </p>

            <div>
                <x-input-label value="Días de la semana" />
                <div class="flex flex-wrap gap-3 mt-1">
                    @foreach ($dayLabels as $value => $label)
                        <label class="inline-flex items-center gap-1 text-sm text-gray-600">
                            <input type="checkbox" wire:model="weekdays" value="{{ $value }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('weekdays')" class="mt-1" />
            </div>

            <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                Activo
            </label>

            <div class="flex justify-end gap-3 pt-2">
                <x-secondary-button type="button" wire:click="$set('showModal', false)">Cancelar</x-secondary-button>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-500">
                    Guardar
                </button>
            </div>
        </form>
    </x-modal>

    <x-modal name="schedule-delete" :show="$confirmingDeleteId !== null" maxWidth="sm" focusable>
        <div class="p-6 space-y-4">
            <h3 class="text-lg font-medium text-gray-900">¿Eliminar este horario?</h3>
            <p class="text-sm text-gray-500">Esta acción no se puede deshacer.</p>
            <div class="flex justify-end gap-3">
                <x-secondary-button type="button" wire:click="$set('confirmingDeleteId', null)">Cancelar</x-secondary-button>
                <x-danger-button wire:click="delete">Eliminar</x-danger-button>
            </div>
        </div>
    </x-modal>
</div>
