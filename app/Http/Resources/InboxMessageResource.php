<?php

namespace App\Http\Resources;

use App\Models\InboxMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InboxMessage
 */
class InboxMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'inbox_id' => $this->id,
            'project' => $this->project->slug,
            'status' => $this->status,
            'reporter' => $this->reporter,
            'text' => $this->raw_text,
            'suggestion' => $this->suggestion,
            'item_key' => $this->item?->key,
            'error' => $this->error,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
