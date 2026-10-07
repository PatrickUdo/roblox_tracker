<?php

namespace App\Mcp\Tools;

use App\Mcp\Markdown;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get_item')]
#[Description('Gets one item by key (for example BONKBOX-42), including its description, comments, activity and the statuses it can move to next.')]
#[IsReadOnly]
class GetItem extends TrackerTool
{
    protected function run(Request $request, User $user): Response
    {
        $validated = $request->validate(['key' => ['required', 'string']]);
        $item = $this->item($validated['key']);

        Gate::forUser($user)->authorize('view', $item);

        return Response::text(Markdown::item($item));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'key' => $schema->string()->description('Item key, for example BONKBOX-42.')->required(),
        ];
    }
}
