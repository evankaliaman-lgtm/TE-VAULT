<?php

namespace App\Http\Requests\Assets;

use App\Enums\AssetAvailabilityStatus;
use App\Enums\AssetCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assets.update') ?? false;
    }

    public function rules(): array
    {
        $asset = $this->route('asset');

        return ['asset_category_id' => ['sometimes', 'integer', Rule::exists('asset_categories', 'id')->whereNull('deleted_at')], 'asset_code' => ['sometimes', 'string', 'max:64', Rule::unique('assets', 'asset_code')->ignore($asset)], 'name' => ['sometimes', 'string', 'max:150'], 'brand' => ['nullable', 'string', 'max:100'], 'model' => ['nullable', 'string', 'max:100'], 'serial_number' => ['nullable', 'string', 'max:128', Rule::unique('assets', 'serial_number')->ignore($asset)], 'condition' => ['sometimes', Rule::enum(AssetCondition::class)], 'availability_status' => ['sometimes', Rule::enum(AssetAvailabilityStatus::class)], 'photo_path' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string']];
    }

    public function messages(): array
    {
        return ['asset_code.unique' => 'The asset code is already in use.'];
    }
}
