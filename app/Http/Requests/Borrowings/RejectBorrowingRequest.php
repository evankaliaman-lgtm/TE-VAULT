<?php

namespace App\Http\Requests\Borrowings;

use Illuminate\Foundation\Http\FormRequest;

class RejectBorrowingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return ['rejection_reason' => ['required', 'string']];
    }

    public function messages(): array
    {
        return ['rejection_reason.required' => 'A rejection reason is required.'];
    }
}
