<?php

namespace App\Actions\Borrowings;

use App\Enums\AssetAvailabilityStatus;
use App\Enums\BorrowingStatus;
use App\Exceptions\AssetUnavailableException;
use App\Exceptions\BorrowingStateException;
use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CancelBorrowingAction
{
    use AuthorizesBorrowingActions;

    public function execute(User $actor, Borrowing $borrowing, ?string $reason = null): Borrowing
    {
        $this->authorize($actor, 'cancel', $borrowing);

        return DB::transaction(function () use ($actor, $borrowing, $reason): Borrowing {
            $asset = Asset::withTrashed()->lockForUpdate()->find($borrowing->asset_id);
            $lockedBorrowing = Borrowing::query()->lockForUpdate()->find($borrowing->id);

            if ($lockedBorrowing === null || ! in_array($lockedBorrowing->status, [BorrowingStatus::Pending, BorrowingStatus::Approved], true)) {
                throw new BorrowingStateException('Only pending or approved borrowings can be cancelled.');
            }

            $wasApproved = $lockedBorrowing->status === BorrowingStatus::Approved;
            if ($wasApproved && ($asset === null || $asset->trashed())) {
                throw new AssetUnavailableException('The reserved asset is unavailable for cancellation.');
            }

            $lockedBorrowing->update([
                'status' => BorrowingStatus::Cancelled,
                'cancelled_by_user_id' => $actor->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            if ($wasApproved) {
                $asset->update(['availability_status' => AssetAvailabilityStatus::Tersedia]);
            }

            return $lockedBorrowing->fresh();
        });
    }
}
