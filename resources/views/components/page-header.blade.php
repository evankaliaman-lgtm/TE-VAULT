@props(['title', 'description' => null, 'actionLabel' => null, 'actionUrl' => null])

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div><x-breadcrumb :items="['Dashboard', $title]" /><h1 class="mt-2 text-2xl font-bold">{{ $title }}</h1>@if ($description)<p class="mt-1 text-sm text-slate-500">{{ $description }}</p>@endif</div>
    @if ($actionLabel && $actionUrl)<a href="{{ $actionUrl }}" class="rounded-lg bg-blue-600 px-4 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-blue-700">{{ $actionLabel }}</a>@endif
</div>
