@component('mail::message')
# TE-VAULT Notification

Borrowing #{{ $borrowing->id }} has been updated.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
