<x-app-layout>
    @if ($dashboard === 'admin')
        @include('dashboard.admin')
    @elseif ($dashboard === 'guru')
        @include('dashboard.guru')
    @else
        @include('dashboard.siswa')
    @endif
</x-app-layout>
