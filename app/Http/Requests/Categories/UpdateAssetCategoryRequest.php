<?php

namespace App\Http\Requests\Categories;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assets.update') ?? false;
    }

    public function rules(): array
    {
        $category = $this->route('asset_category');

        return ['code' => ['sometimes', 'string', 'max:50', Rule::unique('asset_categories', 'code')->ignore($category)], 'name' => ['sometimes', 'string', 'max:100', Rule::unique('asset_categories', 'name')->ignore($category)], 'description' => ['nullable', 'string'], 'is_active' => ['sometimes', 'boolean']];
    }

    public function messages(): array
    {
        return ['code.unique' => 'The category code is already in use.', 'name.unique' => 'The category name is already in use.'];
    }
}
