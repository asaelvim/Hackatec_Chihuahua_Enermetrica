@props(['kwh', 'capacity'])

@php
    // El medidor usa como referencia la capacidad de los bloques Básico +
    // Intermedio 1 + Intermedio 2 (ya prorrateada). Pasarse de ahí entra a
    // Excedente, por eso el indicador se "satura" cerca del extremo rojo.
    $percent = $capacity > 0 ? min(100, max(0, ($kwh / $capacity) * 100)) : 0;
@endphp

<div class="mt-4">
    <div class="relative h-5">
        <div class="absolute -translate-x-1/2 text-gray-600" style="left: {{ $percent }}%;">
            <i class="fa-solid fa-caret-down text-xl leading-none"></i>
        </div>
    </div>

    <div class="h-3 rounded-full" style="background: linear-gradient(to right, #22c55e, #eab308, #f97316, #dc2626);"></div>

    <div class="mt-2 flex items-center gap-2 text-xs text-gray-500">
        <i class="fa-solid fa-house text-emerald-600"></i>
        <span>Este medidor refleja tu nivel de consumo. A menor consumo, mejor tarifa.</span>
    </div>
</div>
