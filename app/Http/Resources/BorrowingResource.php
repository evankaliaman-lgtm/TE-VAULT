<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BorrowingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'borrower_user_id' => $this->borrower_user_id, 'asset_id' => $this->asset_id, 'status' => $this->status, 'requested_at' => $this->requested_at, 'borrowed_at' => $this->borrowed_at, 'due_at' => $this->due_at, 'borrower_note' => $this->borrower_note, 'approved_at' => $this->approved_at, 'rejection_reason' => $this->rejection_reason, 'cancelled_at' => $this->cancelled_at, 'checkout_condition' => $this->checkout_condition, 'return_submitted_at' => $this->return_submitted_at, 'return_evidence_path' => $this->return_evidence_path, 'return_note' => $this->return_note, 'returned_at' => $this->returned_at, 'return_condition' => $this->return_condition, 'borrower' => new UserResource($this->whenLoaded('borrower')), 'asset' => new AssetResource($this->whenLoaded('asset'))];
    }
}
