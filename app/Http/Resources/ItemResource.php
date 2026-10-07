<?php

namespace App\Http\Resources;

use App\Enums\ItemStatus;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Item
 */
class ItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key,
            'number' => $this->number,
            'project' => $this->project->slug,
            'type' => $this->type,
            'status' => $this->status,
            'allowed_transitions' => array_map(fn (ItemStatus $status) => $status->value, $this->status->allowedTransitions()),
            'priority' => $this->priority,
            'title' => $this->title,
            'description' => $this->description,
            'source' => $this->source,
            'reporter' => UserResource::make($this->whenLoaded('reporter')),
            'assignee' => UserResource::make($this->whenLoaded('assignee')),
            'labels' => $this->whenLoaded('labels', fn () => $this->labels->pluck('name')->sort()->values()),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'activity' => ActivityResource::collection($this->whenLoaded('activities')),
            'url' => route('items.show', $this->resource),
            'closed_at' => $this->closed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
