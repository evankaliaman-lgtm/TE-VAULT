<?php

namespace App\Http\Requests\Categories;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assets.create') ?? false;
    }

    public function rules(): array
    {
        return ['code' => ['required', 'string', 'max:50', 'unique:asset_categories,code'], 'name' => ['required', 'string', 'max:100', 'unique:asset_categories,name'], 'description' => ['nullable', 'string'], 'is_active' => ['sometimes', 'boolean']];
    }

    public function messages(): array
    {
        return ['code.unique' => 'The category code is already in use.', 'name.unique' => 'The category name is already in use.'];
    }
}
