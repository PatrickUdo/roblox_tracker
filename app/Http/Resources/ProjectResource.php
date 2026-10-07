<?php

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'open_bugs' => $this->whenHas('open_bugs_count', fn () => (int) $this->resource->getAttribute('open_bugs_count')),
            'open_features' => $this->whenHas('open_features_count', fn () => (int) $this->resource->getAttribute('open_features_count')),
            'labels' => LabelResource::collection($this->whenLoaded('labels')),
            'members' => UserResource::collection($this->whenLoaded('members')),
            'url' => route('projects.board', $this->resource),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
