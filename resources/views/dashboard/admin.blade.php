<section>
    <x-breadcrumb :items="[['label' => 'Dashboard']]" />
    <h1 class="mt-3 text-2xl font-bold tracking-tight">Admin dashboard</h1>
    <p class="mt-1 text-slate-500">Overview of assets and current borrowing activity.</p>
</section>
<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-card title="Total assets" :value="$summary['assets']" description="Registered inventory" />
    <x-card title="Available" :value="$summary['available_assets']" description="Ready to borrow" />
    <x-card title="Pending requests" :value="$summary['pending_borrowings']" description="Awaiting review" />
    <x-card title="Active loans" :value="$summary['active_borrowings']" description="Currently in circulation" />
</section>
