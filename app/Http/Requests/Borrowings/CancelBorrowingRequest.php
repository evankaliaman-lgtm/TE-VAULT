<?php

namespace App\Http\Requests\Borrowings;

use App\Models\Borrowing;
use Illuminate\Foundation\Http\FormRequest;

class CancelBorrowingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $borrowing = $this->route('borrowing');

        return $borrowing instanceof Borrowing && ($this->user()?->can('cancel', $borrowing) ?? false);
    }

    public function rules(): array
    {
        return ['cancellation_reason' => ['nullable', 'string']];
    }

    public function messages(): array
    {
        return [];
    }
}
