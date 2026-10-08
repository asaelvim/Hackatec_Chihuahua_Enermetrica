@props(['kwh', 'capacity'])

@php
    // En el recibo real de CFE, el indicador no se satura al llenar los 3
    // bloques normales (Básico+Intermedio1+Intermedio2): un consumo que ya
    // incluye algo de Excedente todavía cae cerca de la mitad de la barra.
    // Por eso usamos el doble de esa capacidad como referencia del 100%: así
    // llenar los bloques normales (sin excedente) deja el indicador a la
    // mitad, y solo un excedente considerable lo acerca al extremo rojo.
    $scaleMax = $capacity * 2;
    $percent = $scaleMax > 0 ? min(100, max(0, ($kwh / $scaleMax) * 100)) : 0;
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
