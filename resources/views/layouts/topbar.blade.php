<header class="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6">
    <button type="button" @click="sidebarOpen = true" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Open navigation">☰</button>
    <div class="hidden lg:block"><x-breadcrumb :items="[['label' => 'Dashboard']]" /></div>
    <div class="flex items-center gap-3">
        <div class="text-right text-sm">
            <p class="font-semibold text-slate-800">{{ auth()->user()->name }}</p>
            <p class="text-xs text-slate-500">{{ auth()->user()->getRoleNames()->first() }}</p>
        </div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Log out</button></form>
    </div>
</header>
