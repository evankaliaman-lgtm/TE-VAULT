<?php

namespace App\Actions\Borrowings;

use App\Enums\AssetAvailabilityStatus;
use App\Enums\BorrowingStatus;
use App\Exceptions\AssetUnavailableException;
use App\Exceptions\BorrowingConcurrencyException;
use App\Exceptions\BorrowingStateException;
use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ApproveBorrowingAction
{
    use AuthorizesBorrowingActions;

    public function execute(User $admin, Borrowing $borrowing): Borrowing
    {
        $this->authorize($admin, 'approve', $borrowing);

        try {
            return DB::transaction(function () use ($admin, $borrowing): Borrowing {
                $asset = Asset::withTrashed()->lockForUpdate()->find($borrowing->asset_id);
                $lockedBorrowing = Borrowing::query()->lockForUpdate()->find($borrowing->id);

                if ($lockedBorrowing === null || $lockedBorrowing->status !== BorrowingStatus::Pending) {
                    throw new BorrowingStateException('Only pending borrowings can be approved.');
                }
                if ($asset === null || $asset->trashed() || $asset->availability_status !== AssetAvailabilityStatus::Tersedia) {
                    throw new AssetUnavailableException('The asset is no longer available for approval.');
                }
                if (Borrowing::query()->where('asset_id', $asset->id)->whereIn('status', [BorrowingStatus::Approved, BorrowingStatus::Borrowed, BorrowingStatus::ReturnPendingVerification])->exists()) {
                    throw new AssetUnavailableException('The asset already has an active borrowing.');
                }

                $lockedBorrowing->update(['status' => BorrowingStatus::Approved, 'approved_by_user_id' => $admin->id, 'approved_at' => now()]);
                $asset->update(['availability_status' => AssetAvailabilityStatus::Dipesan]);

                return $lockedBorrowing->fresh();
            });
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                throw new BorrowingConcurrencyException('The asset was reserved by another borrowing request.');
            }

            throw $exception;
        }
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['19', '23000'], true);
    }
}
