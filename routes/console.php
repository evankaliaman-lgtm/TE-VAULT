<?php

use App\Enums\BorrowingStatus;
use App\Enums\NotificationStatus;
use App\Jobs\SendBorrowingNotificationJob;
use App\Models\Borrowing;
use App\Models\NotificationLog;
use App\Services\NotificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    Borrowing::query()->where('status', BorrowingStatus::Borrowed)->whereDate('due_at', now()->addDay()->toDateString())->each(fn (Borrowing $borrowing) => app(NotificationService::class)->scheduleReminder($borrowing));
})->dailyAt('08:00')->name('borrowing-reminders');

Schedule::call(function (): void {
    Borrowing::query()->whereIn('status', [BorrowingStatus::Borrowed, BorrowingStatus::ReturnPendingVerification])->where('due_at', '<', now())->each(fn (Borrowing $borrowing) => app(NotificationService::class)->scheduleOverdue($borrowing));
})->hourly()->name('borrowing-overdue');

Schedule::call(function (): void {
    NotificationLog::query()->where('status', NotificationStatus::Failed)->where(fn ($query) => $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))->each(fn (NotificationLog $notification) => SendBorrowingNotificationJob::dispatch($notification));
})->hourly()->name('notification-retries');
