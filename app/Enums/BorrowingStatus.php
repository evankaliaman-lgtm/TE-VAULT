<?php

namespace App\Enums;

enum BorrowingStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Borrowed = 'borrowed';
    case ReturnPendingVerification = 'return_pending_verification';
    case Returned = 'returned';
}
