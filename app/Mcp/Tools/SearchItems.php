<?php

namespace App\Mcp\Tools;

use App\Enums\ItemPriority;
use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Mcp\Markdown;
use App\Models\Project;
use App\Models\User;
use App\Queries\SearchItems as SearchItemsQuery;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('search_items')]
#[Description('Searches bugs and features. Returns one line per item: key, type, status, priority and title. Use get_item for the full item.')]
#[IsReadOnly]
class SearchItems extends TrackerTool
{
    protected function run(Request $request, User $user): Response
    {
        $validated = $request->validate([
            'project' => ['nullable', 'string'],
            'query' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'priority' => ['nullable', 'string'],
            'label' => ['nullable', 'string'],
            'assignee' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $project = null;

        if (filled($validated['project'] ?? null)) {
            $project = $this->project($validated['project']);
            Gate::forUser($user)->authorize('viewItems', $project);
        }

        $query = app(SearchItemsQuery::class)->query($user, $project, [
            'q' => $validated['query'] ?? null,
            'type' => $validated['type'] ?? null,
            'status' => isset($validated['status']) ? explode(',', $validated['status']) : null,
            'priority' => $validated['priority'] ?? null,
            'label' => $validated['label'] ?? null,
            'assignee' => $this->assignee($validated['assignee'] ?? null),
            'sort' => 'updated_at',
        ]);

        if ($project === null) {
            // Only projects whose items this token may read.
            $readable = Project::visibleTo($user)->get()->filter(fn (Project $p) => $user->can('viewItems', $p));
            $query->whereIn('project_id', $readable->modelKeys());
        }

        $limit = (int) ($validated['limit'] ?? 20);
        $items = $query->limit($limit + 1)->get();

        if ($items->isEmpty()) {
            return Response::text('No items found.');
        }

        $text = $items->take($limit)->map(fn ($item) => Markdown::itemLine($item))->implode("\n");

        if ($items->count() > $limit) {
            $text .= "\n\n(More results available; narrow the search or raise the limit.)";
        }

        return Response::text($text);
    }

    private function assignee(?string $value): int|string|null
    {
        return match (true) {
            $value === null, $value === '' => null,
            in_array($value, ['me', 'none'], true) => $value,
            default => $this->assigneeId($value),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()->description('Project slug or key. Omit to search all projects.'),
            'query' => $schema->string()->description('Text to find in title or description, or an item key.'),
            'type' => $schema->string()->enum(array_column(ItemType::cases(), 'value')),
            'status' => $schema->string()->description('One status or several separated by commas: '.implode(', ', array_column(ItemStatus::cases(), 'value')).'.'),
            'priority' => $schema->string()->enum(array_column(ItemPriority::cases(), 'value')),
            'label' => $schema->string()->description('Label name.'),
            'assignee' => $schema->string()->description('E-mail address, "me" or "none".'),
            'limit' => $schema->integer()->description('Maximum number of results (default 20, max 100).'),
        ];
    }
}
