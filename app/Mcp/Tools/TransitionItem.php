<?php

namespace App\Mcp\Tools;

use App\Actions\Items\TransitionItem as TransitionItemAction;
use App\Enums\ItemStatus;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('transition_item')]
#[Description('Changes the status of an item. Allowed: open → in_progress → done → closed, one step at a time, and back to open from any status.')]
class TransitionItem extends TrackerTool
{
    protected function run(Request $request, User $user): Response
    {
        $validated = $request->validate([
            'key' => ['required', 'string'],
            'status' => ['required', 'string'],
        ]);

        $item = app(TransitionItemAction::class)->handle($user, $this->item($validated['key']), $validated['status']);

        return Response::text("{$item->key} is now {$item->status->value}.");
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'key' => $schema->string()->description('Item key, for example BONKBOX-42.')->required(),
            'status' => $schema->string()->enum(array_column(ItemStatus::cases(), 'value'))->required(),
        ];
    }
}
