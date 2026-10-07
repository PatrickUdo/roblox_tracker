<?php

namespace App\Mcp\Tools;

use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

/**
 * Tools only translate between MCP and the Actions; the Actions validate and authorize.
 */
abstract class TrackerTool extends Tool
{
    public function handle(Request $request): Response
    {
        try {
            return $this->run($request, $this->user($request));
        } catch (ModelNotFoundException $e) {
            $ids = implode(', ', $e->getIds());

            return Response::error(match ($e->getModel()) {
                Item::class => "Item {$ids} not found. Item keys look like PROJECTKEY-42.",
                Project::class => "Project {$ids} not found. Use list_projects to see the available projects.",
                default => 'Not found.',
            });
        }
    }

    abstract protected function run(Request $request, User $user): Response;

    protected function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }

    protected function project(string $slugOrKey): Project
    {
        try {
            return Project::findBySlugOrKeyOrFail($slugOrKey);
        } catch (ModelNotFoundException $e) {
            throw $e->setModel(Project::class, [$slugOrKey]);
        }
    }

    protected function item(string $key): Item
    {
        return Item::findByKeyOrFail($key);
    }

    /**
     * Agents name people by e-mail address; "none" (or an empty value) unassigns.
     */
    protected function assigneeId(?string $email): ?int
    {
        if ($email === null || $email === '' || strtolower($email) === 'none') {
            return null;
        }

        return User::where('email', $email)->value('id') ?? -1;
    }
}
