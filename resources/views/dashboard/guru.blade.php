<section>
    <x-breadcrumb :items="[['label' => 'Dashboard']]" />
    <h1 class="mt-3 text-2xl font-bold tracking-tight">Guru dashboard</h1>
    <p class="mt-1 text-slate-500">Track your equipment requests and active loans.</p>
</section>
<section class="grid gap-4 sm:grid-cols-3">
    <x-card title="My requests" :value="$summary['requests']" description="All borrowing requests" />
    <x-card title="Active loans" :value="$summary['active_borrowings']" description="Assets currently borrowed" />
    <x-card title="Awaiting return" :value="$summary['return_pending']" description="Submitted for verification" />
</section>
