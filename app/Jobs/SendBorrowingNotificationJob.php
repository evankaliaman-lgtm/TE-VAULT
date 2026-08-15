<?php

namespace App\Jobs;

use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Mail\BorrowingOverdueMail;
use App\Mail\BorrowingReminderMail;
use App\Models\NotificationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendBorrowingNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(public readonly NotificationLog $notificationLog) {}

    public function handle(): void
    {
        $log = $this->notificationLog->fresh(['borrowing.asset', 'recipient']);
        if ($log === null || $log->status !== NotificationStatus::Pending) {
            return;
        } $mail = $log->notification_type === NotificationType::Overdue ? new BorrowingOverdueMail($log->borrowing) : new BorrowingReminderMail($log->borrowing);
        Mail::to($log->recipient->email)->send($mail);
        $log->update(['status' => NotificationStatus::Sent, 'attempt_count' => $log->attempt_count + 1, 'last_attempt_at' => now(), 'sent_at' => now(), 'error_message' => null]);
    }
}
