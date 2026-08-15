<?php

namespace App\Actions\Borrowings;

use App\Enums\BorrowingStatus;
use App\Exceptions\BorrowingStateException;
use App\Models\Borrowing;
use App\Models\User;
use InvalidArgumentException;

class SubmitReturnAction
{
    use AuthorizesBorrowingActions;

    public function execute(User $actor, Borrowing $borrowing, string $evidencePath, ?string $returnNote = null): Borrowing
    {
        $this->authorize($actor, 'submitReturn', $borrowing);
        if (blank($evidencePath)) {
            throw new InvalidArgumentException('Return evidence is required.');
        }
        if ($borrowing->status !== BorrowingStatus::Borrowed) {
            throw new BorrowingStateException('Only borrowed assets can be submitted for return.');
        }

        $borrowing->update([
            'status' => BorrowingStatus::ReturnPendingVerification,
            'return_submitted_at' => now(),
            'return_evidence_path' => $evidencePath,
            'return_note' => $returnNote,
        ]);

        return $borrowing->fresh();
    }
}
