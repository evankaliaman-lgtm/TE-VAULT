@props(['placeholder' => 'Search...'])
<label class="relative block"><span class="sr-only">Search</span><input x-model.debounce.300ms="search" type="search" placeholder="{{ $placeholder }}" class="w-full rounded-lg border-slate-300 py-2 pl-3 pr-9 text-sm focus:border-blue-500 focus:ring-blue-500"><span class="pointer-events-none absolute right-3 top-2 text-slate-400">⌕</span></label>
