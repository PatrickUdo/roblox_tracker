<?php

namespace App\Http\Controllers\Api;

use App\Actions\Projects\CreateProject;
use App\Actions\Projects\DeleteProject;
use App\Actions\Projects\UpdateProject;
use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreProjectRequest;
use App\Http\Requests\Api\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    /**
     * List projects
     *
     * Projects the token's user is a member of (only the token's project for a project-scoped token).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Project::class);

        /** @var User $user */
        $user = $request->user();

        $projects = Project::visibleTo($user)
            ->withCount([
                'items as open_bugs_count' => fn (Builder $q) => $q->where('type', ItemType::Bug)->whereNot('status', ItemStatus::Closed),
                'items as open_features_count' => fn (Builder $q) => $q->where('type', ItemType::Feature)->whereNot('status', ItemStatus::Closed),
            ])
            ->orderBy('name')
            ->cursorPaginate(50);

        return ProjectResource::collection($projects);
    }

    /**
     * Create a project
     *
     * The creating user becomes its owner. Requires `projects:write` and a token without project scope.
     */
    public function store(StoreProjectRequest $request, CreateProject $create): ProjectResource
    {
        /** @var User $user */
        $user = $request->user();

        return ProjectResource::make($create->handle($user, $request->validated()));
    }

    /**
     * Show a project
     */
    public function show(Project $project): ProjectResource
    {
        Gate::authorize('view', $project);

        return ProjectResource::make($project->load(['labels', 'members']));
    }

    /**
     * Update a project
     *
     * Only the name and description can change; key and slug are fixed. Owners only.
     */
    public function update(UpdateProjectRequest $request, Project $project, UpdateProject $update): ProjectResource
    {
        /** @var User $user */
        $user = $request->user();

        return ProjectResource::make($update->handle($user, $project, $request->validated()));
    }

    /**
     * Delete a project
     *
     * Deletes the project with all its items. Owners only.
     */
    public function destroy(Request $request, Project $project, DeleteProject $delete): Response
    {
        /** @var User $user */
        $user = $request->user();
        $delete->handle($user, $project);

        return response()->noContent();
    }
}
