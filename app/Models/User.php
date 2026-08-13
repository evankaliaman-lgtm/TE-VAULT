<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function guruProfile(): HasOne
    {
        return $this->hasOne(GuruProfile::class);
    }

    public function siswaProfile(): HasOne
    {
        return $this->hasOne(SiswaProfile::class);
    }

    public function borrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class, 'borrower_user_id');
    }

    public function approvedBorrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class, 'approved_by_user_id');
    }

    public function rejectedBorrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class, 'rejected_by_user_id');
    }

    public function cancelledBorrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class, 'cancelled_by_user_id');
    }

    public function verifiedReturns(): HasMany
    {
        return $this->hasMany(Borrowing::class, 'return_verified_by_user_id');
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class, 'recipient_user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_user_id');
    }
}
