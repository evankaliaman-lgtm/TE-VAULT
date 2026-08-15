<?php

namespace App\Actions\Borrowings;

use App\Enums\BorrowingStatus;
use App\Exceptions\BorrowingStateException;
use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RejectBorrowingAction
{
    use AuthorizesBorrowingActions;

    public function execute(User $admin, Borrowing $borrowing, string $reason): Borrowing
    {
        $this->authorize($admin, 'reject', $borrowing);
        if (blank($reason)) {
            throw new InvalidArgumentException('A rejection reason is required.');
        }

        return DB::transaction(function () use ($admin, $borrowing, $reason): Borrowing {
            Asset::withTrashed()->lockForUpdate()->find($borrowing->asset_id);
            $lockedBorrowing = Borrowing::query()->lockForUpdate()->find($borrowing->id);

            if ($lockedBorrowing === null || $lockedBorrowing->status !== BorrowingStatus::Pending) {
                throw new BorrowingStateException('Only pending borrowings can be rejected.');
            }

            $lockedBorrowing->update([
                'status' => BorrowingStatus::Rejected,
                'rejected_by_user_id' => $admin->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return $lockedBorrowing->fresh();
        });
    }
}
