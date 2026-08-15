<?php

namespace App\Support\Borrowings;

use Carbon\CarbonInterface;

class BorrowingDueDateCalculator
{
    public const int DURATION_DAYS = 3;

    public function fromCheckout(CarbonInterface $checkedOutAt): CarbonInterface
    {
        return $checkedOutAt->copy()->addDays(self::DURATION_DAYS);
    }
}
