<?php

namespace App\Queries;

use App\Enums\ItemPriority;
use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * The item filters shared by the API, MCP tools and the web interface.
 */
class SearchItems
{
    public const SORTS = ['number', 'priority', 'status', 'updated_at', 'created_at', 'title'];

    /**
     * @param  array{type?: ?string, status?: string|list<string>|null, priority?: ?string, label?: ?string, assignee?: int|string|null, q?: ?string, sort?: ?string, direction?: ?string}  $filters
     * @return Builder<Item>
     */
    public function query(User $user, ?Project $project, array $filters = []): Builder
    {
        $query = Item::query()->with(['project', 'assignee', 'labels']);

        if ($project !== null) {
            $query->whereBelongsTo($project);
        } else {
            $query->whereIn('project_id', Project::visibleTo($user)->select('id'));
        }

        if ($type = ItemType::tryFrom((string) ($filters['type'] ?? ''))) {
            $query->where('type', $type);
        }

        $statuses = array_filter(array_map(
            fn ($status) => ItemStatus::tryFrom((string) $status),
            (array) ($filters['status'] ?? []),
        ));

        if ($statuses !== []) {
            $query->whereIn('status', $statuses);
        }

        if ($priority = ItemPriority::tryFrom((string) ($filters['priority'] ?? ''))) {
            $query->where('priority', $priority);
        }

        if (filled($filters['label'] ?? null)) {
            $query->whereHas('labels', fn (Builder $labels) => $labels->where('name', $filters['label']));
        }

        $assignee = $filters['assignee'] ?? null;

        match (true) {
            $assignee === 'me' => $query->where('assignee_id', $user->id),
            $assignee === 'none' => $query->whereNull('assignee_id'),
            is_numeric($assignee) => $query->where('assignee_id', (int) $assignee),
            default => null,
        };

        if (filled($q = trim((string) ($filters['q'] ?? '')))) {
            $query->where(function (Builder $search) use ($q) {
                $search->whereLike('title', "%{$q}%")->orWhereLike('description', "%{$q}%");

                if (preg_match('/^[A-Za-z][A-Za-z0-9]*-(\d+)$/', $q, $matches)) {
                    $search->orWhere('number', (int) $matches[1]);
                }
            });
        }

        $this->sort($query, $filters['sort'] ?? null, $filters['direction'] ?? null);

        return $query;
    }

    /**
     * @param  Builder<Item>  $query
     */
    private function sort(Builder $query, ?string $sort, ?string $direction): void
    {
        $sort = in_array($sort, self::SORTS, true) ? $sort : 'number';
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        // Enum columns are stored as strings; order them by meaning, not alphabetically.
        $ranked = match ($sort) {
            'priority' => array_column(ItemPriority::cases(), 'value'),
            'status' => array_column(ItemStatus::cases(), 'value'),
            default => null,
        };

        if ($ranked !== null) {
            $cases = collect($ranked)->map(fn ($value, $rank) => "when '{$value}' then {$rank}")->implode(' ');
            $query->orderByRaw("case {$sort} {$cases} end {$direction}");
        } else {
            $query->orderBy($sort, $direction);
        }

        $query->orderBy('id', $direction);
    }
}
