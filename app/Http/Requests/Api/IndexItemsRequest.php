<?php

namespace App\Http\Requests\Api;

use App\Enums\ItemPriority;
use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Models\Project;
use App\Queries\SearchItems;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && ($this->user()?->can('viewItems', $project) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::enum(ItemType::class)],
            /** One status, or several separated by commas. */
            'status' => ['nullable', 'string'],
            'priority' => ['nullable', Rule::enum(ItemPriority::class)],
            /** Label name. */
            'label' => ['nullable', 'string'],
            /** A user id, "me" or "none". */
            'assignee' => ['nullable', 'string'],
            /** Searches title and description; an item key such as BONKBOX-42 also matches. */
            'q' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', Rule::in(SearchItems::SORTS)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        $filters = $this->safe()->except('per_page');

        if (isset($filters['status'])) {
            $filters['status'] = array_values(array_filter(
                explode(',', (string) $filters['status']),
                fn (string $status) => ItemStatus::tryFrom($status) !== null,
            ));
        }

        return $filters;
    }
}
