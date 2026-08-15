<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Jobs\SendBorrowingNotificationJob;
use App\Mail\BorrowingApprovedMail;
use App\Mail\BorrowingRejectedMail;
use App\Mail\ReturnVerifiedMail;
use App\Models\Borrowing;
use App\Models\NotificationLog;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function queueApproval(Borrowing $borrowing): void
    {
        Mail::to($borrowing->borrower->email)->queue(new BorrowingApprovedMail($borrowing));
    }

    public function queueRejection(Borrowing $borrowing): void
    {
        Mail::to($borrowing->borrower->email)->queue(new BorrowingRejectedMail($borrowing));
    }

    public function queueReturnVerification(Borrowing $borrowing): void
    {
        Mail::to($borrowing->borrower->email)->queue(new ReturnVerifiedMail($borrowing));
    }

    public function scheduleReminder(Borrowing $borrowing): ?NotificationLog
    {
        return $this->schedule($borrowing, NotificationType::PengingatH1, $borrowing->due_at->copy()->subDay());
    }

    public function scheduleOverdue(Borrowing $borrowing): ?NotificationLog
    {
        return $this->schedule($borrowing, NotificationType::Overdue, now());
    }

    public function schedule(Borrowing $borrowing, NotificationType $type, CarbonInterface $scheduledFor): ?NotificationLog
    {
        $key = implode(':', ['borrowing', $borrowing->id, $type->value, $borrowing->due_at->format('YmdHis')]);
        try {
            $log = NotificationLog::query()->firstOrCreate(['idempotency_key' => $key], ['borrowing_id' => $borrowing->id, 'recipient_user_id' => $borrowing->borrower_user_id, 'notification_type' => $type, 'channel' => NotificationChannel::Email, 'scheduled_for' => $scheduledFor, 'status' => NotificationStatus::Pending]);
        } catch (QueryException) {
            return NotificationLog::query()->where('idempotency_key', $key)->first();
        }
        if ($log->wasRecentlyCreated) {
            SendBorrowingNotificationJob::dispatch($log);
        }

        return $log;
    }
}
