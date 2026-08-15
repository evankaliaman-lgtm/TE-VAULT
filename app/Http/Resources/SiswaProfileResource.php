<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiswaProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'user_id' => $this->user_id, 'nis' => $this->nis, 'nisn' => $this->nisn, 'class_name' => $this->class_name, 'phone' => $this->phone];
    }
}
