<?php

namespace App\Http\Requests\Borrowings;

use App\Models\Borrowing;
use Illuminate\Foundation\Http\FormRequest;

class SubmitReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        $borrowing = $this->route('borrowing');

        return $borrowing instanceof Borrowing && ($this->user()?->can('submitReturn', $borrowing) ?? false);
    }

    public function rules(): array
    {
        return ['return_evidence_path' => ['required', 'string', 'max:255'], 'return_note' => ['nullable', 'string']];
    }

    public function messages(): array
    {
        return ['return_evidence_path.required' => 'Return evidence is required.'];
    }
}
