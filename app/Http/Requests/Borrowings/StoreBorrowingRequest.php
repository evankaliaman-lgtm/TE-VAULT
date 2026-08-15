<?php

namespace App\Http\Requests\Borrowings;

use App\Models\Borrowing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBorrowingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Borrowing::class) ?? false;
    }

    public function rules(): array
    {
        return ['asset_id' => ['required', 'integer', Rule::exists('assets', 'id')->whereNull('deleted_at')], 'borrower_note' => ['nullable', 'string']];
    }

    public function messages(): array
    {
        return ['asset_id.exists' => 'The selected asset is unavailable.'];
    }
}
