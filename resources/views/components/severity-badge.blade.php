@props(['score'])

@php
    $score = (float) $score;

    [$label, $classes] = match (true) {
        $score >= 7 => ['Crítica', 'bg-red-100 text-red-700'],
        $score >= 5 => ['Alta', 'bg-orange-100 text-orange-700'],
        $score >= 3 => ['Moderada', 'bg-amber-100 text-amber-700'],
        default => ['Leve', 'bg-gray-100 text-gray-600'],
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium $classes"]) }}>
    {{ $label }}
</span>
