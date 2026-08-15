<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'email' => $this->email, 'roles' => $this->whenLoaded('roles', fn () => $this->getRoleNames()), 'guru_profile' => new GuruProfileResource($this->whenLoaded('guruProfile')), 'siswa_profile' => new SiswaProfileResource($this->whenLoaded('siswaProfile'))];
    }
}
