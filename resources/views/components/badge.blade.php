@props(['tone' => 'slate'])

@php($tones = ['slate' => 'bg-slate-100 text-slate-700', 'emerald' => 'bg-emerald-100 text-emerald-800', 'amber' => 'bg-amber-100 text-amber-800', 'rose' => 'bg-rose-100 text-rose-800'])

<span {{ $attributes->merge(['class' => 'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold '.$tones[$tone]]) }}>{{ $slot }}</span>
