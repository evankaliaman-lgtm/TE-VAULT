<?php

namespace App\Mail;

use App\Models\Borrowing;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BorrowingReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Borrowing $borrowing) {}

    public function build(): static
    {
        return $this->subject('Borrowing reminder')->markdown('mail.borrowing-status');
    }
}
