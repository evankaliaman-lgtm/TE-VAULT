<?php

namespace App\Http\Requests\Borrowings;

use App\Enums\AssetCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return ['return_condition' => ['required', Rule::enum(AssetCondition::class)], 'return_verification_note' => ['nullable', 'string']];
    }

    public function messages(): array
    {
        return ['return_condition.required' => 'Return condition is required.'];
    }
}
