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
use App\Support\Borrowings\BorrowingDueDateCalculator;
use Illuminate\Support\Facades\DB;

class CheckoutBorrowingAction
{
    use AuthorizesBorrowingActions;

    public function __construct(private readonly BorrowingDueDateCalculator $dueDates) {}

    public function execute(User $admin, Borrowing $borrowing, AssetCondition $checkoutCondition): Borrowing
    {
        $this->authorize($admin, 'checkout', $borrowing);

        return DB::transaction(function () use ($borrowing, $checkoutCondition): Borrowing {
            $asset = Asset::withTrashed()->lockForUpdate()->find($borrowing->asset_id);
            $lockedBorrowing = Borrowing::query()->lockForUpdate()->find($borrowing->id);

            if ($lockedBorrowing === null || $lockedBorrowing->status !== BorrowingStatus::Approved) {
                throw new BorrowingStateException('Only approved borrowings can be checked out.');
            }
            if ($asset === null || $asset->trashed() || $asset->availability_status !== AssetAvailabilityStatus::Dipesan) {
                throw new AssetUnavailableException('The asset is not reserved for this checkout.');
            }

            $borrowedAt = now();
            $lockedBorrowing->update([
                'status' => BorrowingStatus::Borrowed,
                'borrowed_at' => $borrowedAt,
                'due_at' => $this->dueDates->fromCheckout($borrowedAt),
                'checkout_condition' => $checkoutCondition,
            ]);
            $asset->update(['availability_status' => AssetAvailabilityStatus::Dipinjam]);

            return $lockedBorrowing->fresh();
        });
    }
}
