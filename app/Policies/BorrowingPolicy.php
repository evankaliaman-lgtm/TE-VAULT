<?php

namespace App\Policies;

use App\Enums\BorrowingStatus;
use App\Models\Borrowing;
use App\Models\User;

class BorrowingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'guru', 'siswa']);
    }

    public function view(User $user, Borrowing $borrowing): bool
    {
        return $user->hasRole('admin') || $this->owns($user, $borrowing);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['guru', 'siswa']);
    }

    public function approve(User $user, Borrowing $borrowing): bool
    {
        return $user->hasRole('admin');
    }

    public function reject(User $user, Borrowing $borrowing): bool
    {
        return $user->hasRole('admin');
    }

    public function cancel(User $user, Borrowing $borrowing): bool
    {
        return $user->hasRole('admin') || ($this->owns($user, $borrowing)
            && in_array($borrowing->status, [BorrowingStatus::Pending, BorrowingStatus::Approved], true));
    }

    public function checkout(User $user, Borrowing $borrowing): bool
    {
        return $user->hasRole('admin');
    }

    public function submitReturn(User $user, Borrowing $borrowing): bool
    {
        return $user->hasRole('admin') || ($this->owns($user, $borrowing)
            && $borrowing->status === BorrowingStatus::Borrowed);
    }

    public function verifyReturn(User $user, Borrowing $borrowing): bool
    {
        return $user->hasRole('admin');
    }

    private function owns(User $user, Borrowing $borrowing): bool
    {
        return $borrowing->borrower_user_id === $user->id;
    }
}
