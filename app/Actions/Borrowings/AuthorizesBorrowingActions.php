<?php

namespace App\Actions\Borrowings;

use App\Exceptions\UnauthorizedBorrowingActionException;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

trait AuthorizesBorrowingActions
{
    private function authorize(User $actor, string $ability, Model|string $subject): void
    {
        if (! Gate::forUser($actor)->allows($ability, $subject)) {
            throw new UnauthorizedBorrowingActionException('You are not authorized to '.$ability.' this borrowing.');
        }
    }
}
