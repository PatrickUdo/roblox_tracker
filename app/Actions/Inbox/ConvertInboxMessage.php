<?php

namespace App\Actions\Inbox;

use App\Actions\Items\CreateItem;
use App\Enums\InboxStatus;
use App\Enums\ItemSource;
use App\Models\InboxMessage;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Turns an inbox message into an item, from the LLM suggestion with optional overrides.
 */
class ConvertInboxMessage
{
    public function __construct(private CreateItem $createItem) {}

    /**
     * @param  array<string, mixed>  $overrides  type?, title?, description?, priority?, assignee_id?, labels?
     */
    public function handle(User $actor, InboxMessage $message, array $overrides = []): Item
    {
        Gate::forUser($actor)->authorize('reviewInbox', $message->project);

        return DB::transaction(function () use ($actor, $message, $overrides) {
            $locked = InboxMessage::query()->whereKey($message->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->isOpen()) {
                throw ValidationException::withMessages(['message' => 'This inbox message has already been handled.']);
            }

            $suggestion = $locked->suggestion ?? [];
            $data = [
                'type' => $suggestion['type'] ?? null,
                'title' => $suggestion['title'] ?? null,
                'description' => $suggestion['description'] ?? null,
                'priority' => $suggestion['priority'] ?? null,
                ...$overrides,
            ];
            $data['description'] = $this->withOrigin((string) ($data['description'] ?? ''), $locked);

            $item = $this->createItem->handle($actor, $locked->project, $data, ItemSource::Inbox);

            $locked->update(['status' => InboxStatus::Converted, 'item_id' => $item->id, 'error' => null]);
            $message->setRawAttributes($locked->getAttributes(), true);

            return $item;
        });
    }

    private function withOrigin(string $description, InboxMessage $message): string
    {
        $reporter = $message->reporter !== null ? " door {$message->reporter}" : '';

        return trim($description."\n\n---\n_Gemeld{$reporter} via de voice-agent._");
    }
}
