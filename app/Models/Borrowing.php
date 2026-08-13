<?php

namespace App\Models;

use App\Enums\AssetCondition;
use App\Enums\BorrowingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Borrowing extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'borrower_user_id',
        'asset_id',
        'status',
        'requested_at',
        'borrowed_at',
        'due_at',
        'borrowing_evidence_path',
        'borrower_note',
        'approved_by_user_id',
        'approved_at',
        'approval_note',
        'rejected_by_user_id',
        'rejected_at',
        'rejection_reason',
        'cancelled_by_user_id',
        'cancelled_at',
        'cancellation_reason',
        'checkout_condition',
        'return_submitted_at',
        'return_evidence_path',
        'return_note',
        'returned_at',
        'return_condition',
        'return_verified_by_user_id',
        'return_verified_at',
        'return_verification_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BorrowingStatus::class,
            'requested_at' => 'datetime',
            'borrowed_at' => 'datetime',
            'due_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'checkout_condition' => AssetCondition::class,
            'return_submitted_at' => 'datetime',
            'returned_at' => 'datetime',
            'return_condition' => AssetCondition::class,
            'return_verified_at' => 'datetime',
        ];
    }

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'borrower_user_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    public function returnVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'return_verified_by_user_id');
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, [
            BorrowingStatus::Borrowed,
            BorrowingStatus::ReturnPendingVerification,
        ], true)
            && $this->returned_at === null
            && $this->due_at?->isPast() === true;
    }
}
