<?php

namespace App\Mcp\Tools;

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list_projects')]
#[Description('Lists the projects this token can access, with their slug, key and number of open bugs and features.')]
#[IsReadOnly]
class ListProjects extends TrackerTool
{
    protected function run(Request $request, User $user): Response
    {
        Gate::forUser($user)->authorize('viewAny', Project::class);

        $projects = Project::visibleTo($user)
            ->withCount([
                'items as open_bugs_count' => fn (Builder $q) => $q->where('type', ItemType::Bug)->whereIn('status', [ItemStatus::Open, ItemStatus::InProgress]),
                'items as open_features_count' => fn (Builder $q) => $q->where('type', ItemType::Feature)->whereIn('status', [ItemStatus::Open, ItemStatus::InProgress]),
            ])
            ->orderBy('name')
            ->get();

        if ($projects->isEmpty()) {
            return Response::text('No projects available for this token.');
        }

        return Response::text($projects->map(fn (Project $p) => sprintf(
            '- %s (slug: %s, key: %s): %d open bugs, %d open features',
            $p->name, $p->slug, $p->key, $p->getAttribute('open_bugs_count'), $p->getAttribute('open_features_count'),
        ))->implode("\n"));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
