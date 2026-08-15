<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'actor_user_id' => $this->actor_user_id, 'action' => $this->action, 'entity_type' => $this->entity_type, 'entity_id' => $this->entity_id, 'old_values' => $this->old_values, 'new_values' => $this->new_values, 'metadata' => $this->metadata, 'created_at' => $this->created_at, 'actor' => new UserResource($this->whenLoaded('actor'))];
    }
}
