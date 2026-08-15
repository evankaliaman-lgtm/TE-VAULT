<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'borrowing_id' => $this->borrowing_id, 'recipient_user_id' => $this->recipient_user_id, 'notification_type' => $this->notification_type, 'channel' => $this->channel, 'scheduled_for' => $this->scheduled_for, 'status' => $this->status, 'attempt_count' => $this->attempt_count, 'sent_at' => $this->sent_at, 'borrowing' => new BorrowingResource($this->whenLoaded('borrowing')), 'recipient' => new UserResource($this->whenLoaded('recipient'))];
    }
}
