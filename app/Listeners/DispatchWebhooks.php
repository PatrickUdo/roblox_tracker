<?php

namespace App\Listeners;

use App\Events\CommentAdded;
use App\Events\ItemCreated;
use App\Events\ItemTransitioned;
use App\Events\ItemUpdated;
use App\Http\Resources\ItemResource;
use App\Jobs\DeliverWebhook;
use App\Models\Item;
use App\Models\User;

class DispatchWebhooks
{
    public function handleItemCreated(ItemCreated $event): void
    {
        $this->send('item.created', $event->item, $event->actor);
    }

    public function handleItemUpdated(ItemUpdated $event): void
    {
        $this->send('item.updated', $event->item, $event->actor, ['old' => $event->old, 'new' => $event->new]);
    }

    public function handleItemTransitioned(ItemTransitioned $event): void
    {
        $this->send('item.transitioned', $event->item, $event->actor, [
            'old' => ['status' => $event->from->value],
            'new' => ['status' => $event->to->value],
        ]);
    }

    public function handleCommentAdded(CommentAdded $event): void
    {
        $this->send('comment.added', $event->comment->item, $event->actor, ['comment' => [
            'id' => $event->comment->id,
            'body' => $event->comment->body,
        ]]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function send(string $name, Item $item, ?User $actor, array $extra = []): void
    {
        $webhooks = $item->project->webhooks()->get();

        if ($webhooks->isEmpty()) {
            return;
        }

        $payload = [
            'event' => $name,
            'project' => $item->project->slug,
            'actor' => $actor === null ? null : ['id' => $actor->id, 'name' => $actor->name],
            'item' => ItemResource::make($item->load(['project', 'reporter', 'assignee', 'labels']))->resolve(),
            ...$extra,
            'sent_at' => now()->toIso8601String(),
        ];

        foreach ($webhooks as $webhook) {
            DeliverWebhook::dispatch($webhook, $payload);
        }
    }
}
