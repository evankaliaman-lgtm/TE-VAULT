<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'asset_category_id' => $this->asset_category_id, 'asset_code' => $this->asset_code, 'name' => $this->name, 'brand' => $this->brand, 'model' => $this->model, 'serial_number' => $this->serial_number, 'condition' => $this->condition, 'availability_status' => $this->availability_status, 'photo_path' => $this->photo_path, 'notes' => $this->notes, 'category' => new AssetCategoryResource($this->whenLoaded('category'))];
    }
}
