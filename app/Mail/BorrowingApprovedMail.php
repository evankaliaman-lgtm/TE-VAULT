<?php

namespace App\Mail;

use App\Models\Borrowing;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BorrowingApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Borrowing $borrowing) {}

    public function build(): static
    {
        return $this->subject('Borrowing approved')->markdown('mail.borrowing-status');
    }
}
