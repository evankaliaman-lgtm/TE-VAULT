@props(['title' => null, 'value' => null, 'description' => null])

<section {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white p-5 shadow-sm']) }}>
    @if ($title)
        <p class="text-sm font-medium text-slate-500">{{ $title }}</p>
    @endif

    @if (! is_null($value))
        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $value }}</p>
    @endif

    @if ($description)
        <p class="mt-2 text-sm text-slate-500">{{ $description }}</p>
    @endif

    {{ $slot }}
</section>
