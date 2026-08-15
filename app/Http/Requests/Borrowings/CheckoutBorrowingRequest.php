<?php

namespace App\Http\Requests\Borrowings;

use App\Enums\AssetCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutBorrowingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return ['checkout_condition' => ['required', Rule::enum(AssetCondition::class)]];
    }

    public function messages(): array
    {
        return ['checkout_condition.required' => 'Checkout condition is required.'];
    }
}
