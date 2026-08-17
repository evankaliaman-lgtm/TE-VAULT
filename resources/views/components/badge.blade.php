@props(['tone' => 'slate'])

@php
    $tones = [
        'emerald' => 'bg-emerald-100 text-emerald-800',
        'amber' => 'bg-amber-100 text-amber-800',
        'rose' => 'bg-rose-100 text-rose-800',
        'blue' => 'bg-blue-100 text-blue-800',
        'slate' => 'bg-slate-100 text-slate-700',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold '.$tones[$tone]]) }}>
    {{ $slot }}
</span>
