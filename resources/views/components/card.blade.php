@props(['title', 'value', 'description' => null])

<section {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white p-5 shadow-sm']) }}>
    <p class="text-sm font-medium text-slate-500">{{ $title }}</p>
    <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $value }}</p>
    @if ($description)
        <p class="mt-2 text-sm text-slate-500">{{ $description }}</p>
    @endif
    {{ $slot }}
</section>
