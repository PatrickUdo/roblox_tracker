<?php

namespace App\Mcp\Tools;

use App\Actions\Items\UpdateItem as UpdateItemAction;
use App\Enums\ItemPriority;
use App\Enums\ItemType;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('update_item')]
#[Description('Changes fields of an item. Only the fields you pass are changed; labels replaces the full label list. Use transition_item to change the status.')]
#[IsIdempotent]
class UpdateItem extends TrackerTool
{
    protected function run(Request $request, User $user): Response
    {
        $validated = $request->validate([
            'key' => ['required', 'string'],
            'assignee' => ['nullable', 'string'],
        ]);

        $item = $this->item($validated['key']);
        $data = array_filter(
            $request->all(['type', 'title', 'description', 'priority', 'labels']),
            fn ($value) => $value !== null,
        );

        if ($request->get('assignee', false) !== false) {
            $data['assignee_id'] = $this->assigneeId($validated['assignee'] ?? null);
        }

        app(UpdateItemAction::class)->handle($user, $item, $data);

        return Response::text("Updated {$item->key}.");
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'key' => $schema->string()->description('Item key, for example BONKBOX-42.')->required(),
            'title' => $schema->string(),
            'description' => $schema->string()->description('Markdown; replaces the whole description.'),
            'type' => $schema->string()->enum(array_column(ItemType::cases(), 'value')),
            'priority' => $schema->string()->enum(array_column(ItemPriority::cases(), 'value')),
            'assignee' => $schema->string()->description('E-mail address of a project member, or "none" to unassign.'),
            'labels' => $schema->array()->items($schema->string())->description('Names of existing project labels; replaces the current labels.'),
        ];
    }
}
