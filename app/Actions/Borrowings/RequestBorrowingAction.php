<?php

namespace App\Actions\Borrowings;

use App\Enums\AssetAvailabilityStatus;
use App\Enums\BorrowingStatus;
use App\Exceptions\AssetUnavailableException;
use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\User;
use App\Support\Borrowings\BorrowingDueDateCalculator;
use Illuminate\Support\Facades\DB;

class RequestBorrowingAction
{
    use AuthorizesBorrowingActions;

    public function __construct(private readonly BorrowingDueDateCalculator $dueDates) {}

    public function execute(User $borrower, Asset $asset, ?string $borrowerNote = null): Borrowing
    {
        $this->authorize($borrower, 'create', Borrowing::class);

        return DB::transaction(function () use ($borrower, $asset, $borrowerNote): Borrowing {
            $lockedAsset = Asset::withTrashed()->lockForUpdate()->find($asset->id);

            if ($lockedAsset === null || $lockedAsset->trashed() || $lockedAsset->availability_status !== AssetAvailabilityStatus::Tersedia) {
                throw new AssetUnavailableException('The asset is not available for borrowing.');
            }

            $requestedAt = now();

            return Borrowing::query()->create([
                'borrower_user_id' => $borrower->id,
                'asset_id' => $lockedAsset->id,
                'status' => BorrowingStatus::Pending,
                'requested_at' => $requestedAt,
                'due_at' => $this->dueDates->fromCheckout($requestedAt),
                'borrower_note' => $borrowerNote,
            ]);
        });
    }
}
