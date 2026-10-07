@props(['status'])

@php
    $styles = match ($status) {
        'on' => 'bg-emerald-100 text-emerald-700',
        'off' => 'bg-gray-100 text-gray-600',
        'offline' => 'bg-red-100 text-red-700',
        'maintenance' => 'bg-amber-100 text-amber-700',
        default => 'bg-gray-100 text-gray-600',
    };

    $labels = [
        'on' => 'Encendido',
        'off' => 'Apagado',
        'offline' => 'Sin conexión',
        'maintenance' => 'Mantenimiento',
    ];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$styles}"]) }}>
    {{ $labels[$status] ?? ucfirst($status) }}
</span>
