<?php

namespace App\Actions\Items;

use App\Enums\ItemPriority;
use App\Enums\ItemType;
use App\Models\Project;
use Illuminate\Validation\Rule;

/**
 * Field rules shared by CreateItem and UpdateItem.
 */
class ItemRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function for(Project $project): array
    {
        return [
            'type' => ['required', Rule::enum(ItemType::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'priority' => ['required', Rule::enum(ItemPriority::class)],
            'assignee_id' => ['nullable', 'integer', Rule::exists('project_user', 'user_id')->where('project_id', $project->id)],
            'labels' => ['array'],
            'labels.*' => ['string', 'distinct', Rule::exists('labels', 'name')->where('project_id', $project->id)],
        ];
    }
}
