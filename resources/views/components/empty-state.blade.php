@props(['title' => 'No data yet', 'description' => null])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center']) }}>
    <p class="font-semibold text-slate-800">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
