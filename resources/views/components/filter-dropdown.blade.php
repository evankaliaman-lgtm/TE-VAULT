@props(['options' => []])
<select x-model="filter" class="rounded-lg border-slate-300 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"><option value="">All</option>@foreach($options as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
