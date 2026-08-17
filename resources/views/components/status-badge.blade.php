@props(['status'])
@php($tone = match($status) {'tersedia', 'approved', 'returned', 'baik', 'aktif' => 'emerald', 'pending', 'dipesan', 'rusak_ringan' => 'amber', 'rejected', 'cancelled', 'rusak_berat', 'perbaikan' => 'rose', default => 'slate'})
<x-badge :tone="$tone">{{ str($status)->replace('_', ' ')->title() }}</x-badge>
