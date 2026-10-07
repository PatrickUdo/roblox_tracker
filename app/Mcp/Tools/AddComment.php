<?php

namespace App\Mcp\Tools;

use App\Actions\Comments\AddComment as AddCommentAction;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('add_comment')]
#[Description('Adds a comment (markdown) to an item.')]
class AddComment extends TrackerTool
{
    protected function run(Request $request, User $user): Response
    {
        $validated = $request->validate(['key' => ['required', 'string']]);
        $item = $this->item($validated['key']);

        app(AddCommentAction::class)->handle($user, $item, ['body' => $request->get('body')]);

        return Response::text("Comment added to {$item->key}.");
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'key' => $schema->string()->description('Item key, for example BONKBOX-42.')->required(),
            'body' => $schema->string()->description('Markdown.')->required(),
        ];
    }
}
