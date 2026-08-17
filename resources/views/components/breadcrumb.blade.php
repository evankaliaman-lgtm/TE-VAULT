@props(['items' => []])

<nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm text-slate-500">
    @foreach ($items as $item)
        @unless ($loop->first)
            <span aria-hidden="true">/</span>
        @endunless

        <span @if (! $loop->first) aria-current="page" @endif class="{{ $loop->last ? 'font-medium text-slate-700' : '' }}">
            {{ $item }}
        </span>
    @endforeach
</nav>
