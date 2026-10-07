<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
class extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?int $confirmingDeleteId = null;

    public function with(): array
    {
        return [
            'users' => User::orderBy('name')->paginate(10),
        ];
    }

    public function create(): void
    {
        $this->reset(['name', 'email', 'password', 'password_confirmation']);
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
        ]);

        $this->showModal = false;
        $this->reset(['name', 'email', 'password', 'password_confirmation']);
        $this->dispatch('close');
        session()->flash('status', 'Usuario creado correctamente.');
    }

    public function confirmDelete(int $userId): void
    {
        if ($userId === auth()->id()) {
            session()->flash('error', 'No puedes eliminar tu propio usuario.');

            return;
        }

        $this->confirmingDeleteId = $userId;
    }

    public function delete(): void
    {
        if ($this->confirmingDeleteId === auth()->id()) {
            $this->confirmingDeleteId = null;

            return;
        }

        User::findOrFail($this->confirmingDeleteId)->delete();
        $this->confirmingDeleteId = null;
        $this->dispatch('close');
        session()->flash('status', 'Usuario eliminado correctamente.');
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Usuarios</h2>
            <button type="button" wire:click="create" x-data="" x-on:click="$dispatch('open-modal', 'user-form')"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 transition">
                + Agregar usuario
            </button>
        </div>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        @if (session('status'))
            <div class="rounded-md bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-md bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
                {{ session('error') }}
            </div>
        @endif

        <x-card class="!p-0 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Correo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registrado</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-800">
                                {{ $user->name }}
                                @if ($user->id === auth()->id())
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-700">Tú</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $user->email }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $user->created_at->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 text-right text-sm">
                                @if ($user->id !== auth()->id())
                                    <button wire:click="confirmDelete({{ $user->id }})" x-data="" x-on:click="$dispatch('open-modal', 'user-delete')" class="text-red-600 hover:text-red-800">Eliminar</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-400">No hay usuarios registrados todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>

        {{ $users->links() }}
    </div>

    <!-- Modal crear usuario -->
    <x-modal name="user-form" :show="$showModal" maxWidth="md" focusable>
        <form wire:submit="save" class="p-6 space-y-4">
            <h3 class="text-lg font-medium text-gray-900">Nuevo usuario</h3>

            <div>
                <x-input-label for="name" value="Nombre" />
                <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="email" value="Correo" />
                <x-text-input wire:model="email" id="email" type="email" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="password" value="Contraseña" />
                <x-text-input wire:model="password" id="password" type="password" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('password')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="password_confirmation" value="Confirmar contraseña" />
                <x-text-input wire:model="password_confirmation" id="password_confirmation" type="password" class="mt-1 block w-full" />
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-secondary-button type="button" wire:click="$set('showModal', false)" x-data="" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-500">
                    Crear usuario
                </button>
            </div>
        </form>
    </x-modal>

    <!-- Modal confirmar eliminación -->
    <x-modal name="user-delete" :show="$confirmingDeleteId !== null" maxWidth="sm" focusable>
        <div class="p-6 space-y-4">
            <h3 class="text-lg font-medium text-gray-900">¿Eliminar este usuario?</h3>
            <p class="text-sm text-gray-500">
                Perderá acceso inmediato al sistema. Esta acción no se puede deshacer.
            </p>
            <div class="flex justify-end gap-3">
                <x-secondary-button type="button" wire:click="$set('confirmingDeleteId', null)" x-data="" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-danger-button wire:click="delete">Eliminar</x-danger-button>
            </div>
        </div>
    </x-modal>
</div>
