<?php

namespace App\Mcp\Tools;

use App\Actions\Items\CreateItem as CreateItemAction;
use App\Enums\ItemPriority;
use App\Enums\ItemSource;
use App\Enums\ItemType;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('create_item')]
#[Description('Creates a bug or feature in a project and returns its key. The description supports markdown.')]
class CreateItem extends TrackerTool
{
    protected function run(Request $request, User $user): Response
    {
        $validated = $request->validate([
            'project' => ['required', 'string'],
            'assignee' => ['nullable', 'string'],
        ]);

        $project = $this->project($validated['project']);
        $data = $request->all(['type', 'title', 'description', 'priority', 'labels']);

        if (array_key_exists('assignee', $validated)) {
            $data['assignee_id'] = $this->assigneeId($validated['assignee']);
        }

        $item = app(CreateItemAction::class)->handle($user, $project, $data, ItemSource::Mcp);

        return Response::text("Created {$item->key}: {$item->title}\n".route('items.show', $item));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()->description('Project slug or key.')->required(),
            'type' => $schema->string()->enum(array_column(ItemType::cases(), 'value'))->required(),
            'title' => $schema->string()->description('Short, specific title.')->required(),
            'description' => $schema->string()->description('Markdown. For bugs: steps to reproduce, expected and actual behaviour.')->required(),
            'priority' => $schema->string()->enum(array_column(ItemPriority::cases(), 'value'))->description('Defaults to medium.'),
            'labels' => $schema->array()->items($schema->string())->description('Names of existing project labels.'),
            'assignee' => $schema->string()->description('E-mail address of a project member.'),
        ];
    }
}
