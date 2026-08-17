@props(['headings' => []])

<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
    <table {{ $attributes->merge(['class' => 'min-w-full divide-y divide-slate-200 text-left text-sm']) }}>
        @if ($headings)
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    @foreach ($headings as $heading)
                        <th scope="col" class="px-5 py-3 font-semibold">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody class="divide-y divide-slate-100 text-slate-700">
            {{ $slot }}
        </tbody>
    </table>
</div>
