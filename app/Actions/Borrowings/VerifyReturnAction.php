<?php

namespace App\Actions\Borrowings;

use App\Enums\AssetAvailabilityStatus;
use App\Enums\AssetCondition;
use App\Enums\BorrowingStatus;
use App\Exceptions\AssetUnavailableException;
use App\Exceptions\BorrowingStateException;
use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class VerifyReturnAction
{
    use AuthorizesBorrowingActions;

    public function execute(User $admin, Borrowing $borrowing, AssetCondition $returnCondition, ?string $verificationNote = null): Borrowing
    {
        $this->authorize($admin, 'verifyReturn', $borrowing);

        return DB::transaction(function () use ($admin, $borrowing, $returnCondition, $verificationNote): Borrowing {
            $asset = Asset::withTrashed()->lockForUpdate()->find($borrowing->asset_id);
            $lockedBorrowing = Borrowing::query()->lockForUpdate()->find($borrowing->id);

            if ($lockedBorrowing === null || $lockedBorrowing->status !== BorrowingStatus::ReturnPendingVerification) {
                throw new BorrowingStateException('Only submitted returns can be verified.');
            }
            if ($asset === null || $asset->trashed()) {
                throw new AssetUnavailableException('The asset is unavailable for return verification.');
            }

            $lockedBorrowing->update([
                'status' => BorrowingStatus::Returned,
                'returned_at' => now(),
                'return_condition' => $returnCondition,
                'return_verified_by_user_id' => $admin->id,
                'return_verified_at' => now(),
                'return_verification_note' => $verificationNote,
            ]);
            $asset->update(['availability_status' => $returnCondition === AssetCondition::RusakBerat
                ? AssetAvailabilityStatus::Perbaikan
                : AssetAvailabilityStatus::Tersedia]);

            return $lockedBorrowing->fresh();
        });
    }
}
