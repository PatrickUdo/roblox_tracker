<?php

namespace App\Mcp\Resources;

use App\Mcp\Markdown;
use App\Models\Project;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name('project')]
#[Description('A project: description, labels, members and its open and in-progress items. Useful as context at the start of a session.')]
#[MimeType('text/markdown')]
class ProjectResource extends Resource implements HasUriTemplate
{
    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('project://{slug}');
    }

    public function handle(Request $request): Response
    {
        try {
            $project = Project::findBySlugOrKeyOrFail((string) $request->get('slug'));
        } catch (ModelNotFoundException) {
            return Response::error('Project not found.');
        }

        Gate::forUser($request->user())->authorize('viewItems', $project);

        return Response::text(Markdown::project($project->load(['labels', 'members'])));
    }
}
