<?php

namespace App\Http\Requests\Assets;

use App\Enums\AssetAvailabilityStatus;
use App\Enums\AssetCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assets.create') ?? false;
    }

    public function rules(): array
    {
        return ['asset_category_id' => ['required', 'integer', Rule::exists('asset_categories', 'id')->whereNull('deleted_at')], 'asset_code' => ['required', 'string', 'max:64', 'unique:assets,asset_code'], 'name' => ['required', 'string', 'max:150'], 'brand' => ['nullable', 'string', 'max:100'], 'model' => ['nullable', 'string', 'max:100'], 'serial_number' => ['nullable', 'string', 'max:128', 'unique:assets,serial_number'], 'condition' => ['required', Rule::enum(AssetCondition::class)], 'availability_status' => ['sometimes', Rule::enum(AssetAvailabilityStatus::class)], 'photo_path' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string']];
    }

    public function messages(): array
    {
        return ['asset_category_id.exists' => 'The selected asset category is invalid.', 'asset_code.unique' => 'The asset code is already in use.'];
    }
}
