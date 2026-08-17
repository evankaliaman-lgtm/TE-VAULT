@props(['items' => []])

<nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm text-slate-500">
    @foreach ($items as $item)
        @if (! $loop->first)
            <span aria-hidden="true">/</span>
        @endif

        @if (! empty($item['url']))
            <a href="{{ $item['url'] }}" class="hover:text-slate-900">{{ $item['label'] }}</a>
        @else
            <span class="font-medium text-slate-700" aria-current="page">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
