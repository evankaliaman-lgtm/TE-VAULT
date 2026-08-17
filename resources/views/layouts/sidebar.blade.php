<aside class="flex h-full w-72 flex-col bg-slate-900 p-4 text-slate-200">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 text-lg font-bold text-white">
        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600 text-sm">TV</span> TE-VAULT
    </a>

    <nav class="mt-8 space-y-1" aria-label="Primary navigation">
        <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800' }}">Dashboard</a>

        @can('assets.view')
            <span class="block rounded-lg px-3 py-2 text-sm text-slate-300">Assets</span>
        @endcan

        @role('guru|siswa')
            @can('borrowings.create')
                <span class="block rounded-lg px-3 py-2 text-sm text-slate-300">My Borrowings</span>
            @endcan
        @endrole

        @role('admin')
            <p class="px-3 pt-5 text-xs font-semibold uppercase tracking-wider text-slate-500">Administration</p>
            <span class="mt-1 block rounded-lg px-3 py-2 text-sm">Asset Management</span>
            <span class="block rounded-lg px-3 py-2 text-sm">Borrowing Approval</span>
            <span class="block rounded-lg px-3 py-2 text-sm">Audit Logs</span>
        @endrole
    </nav>

    <a href="{{ route('profile.edit') }}" class="mt-auto rounded-lg px-3 py-2 text-sm hover:bg-slate-800">Profile</a>
</aside>
