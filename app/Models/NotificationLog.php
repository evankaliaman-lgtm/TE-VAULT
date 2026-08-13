<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'borrowing_id',
        'recipient_user_id',
        'notification_type',
        'channel',
        'scheduled_for',
        'status',
        'idempotency_key',
        'attempt_count',
        'last_attempt_at',
        'next_attempt_at',
        'sent_at',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'notification_type' => NotificationType::class,
            'channel' => NotificationChannel::class,
            'status' => NotificationStatus::class,
            'scheduled_for' => 'datetime',
            'last_attempt_at' => 'datetime',
            'next_attempt_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function borrowing(): BelongsTo
    {
        return $this->belongsTo(Borrowing::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }
}
