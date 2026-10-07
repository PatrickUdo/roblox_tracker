<?php

namespace App\Listeners;

use App\Events\CommentAdded;
use App\Events\ItemCreated;
use App\Events\ItemTransitioned;
use App\Events\ItemUpdated;
use App\Models\Item;
use App\Models\User;

class RecordItemActivity
{
    public function handleItemCreated(ItemCreated $event): void
    {
        $this->record($event->item, $event->actor, 'created', null, [
            'status' => $event->item->status->value,
            'source' => $event->item->source->value,
        ]);
    }

    public function handleItemUpdated(ItemUpdated $event): void
    {
        $this->record($event->item, $event->actor, 'updated', $event->old, $event->new);
    }

    public function handleItemTransitioned(ItemTransitioned $event): void
    {
        $this->record($event->item, $event->actor, 'transitioned', ['status' => $event->from->value], ['status' => $event->to->value]);
    }

    public function handleCommentAdded(CommentAdded $event): void
    {
        $this->record($event->comment->item, $event->actor, 'commented', null, ['comment_id' => $event->comment->id]);
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    private function record(Item $item, ?User $actor, string $event, ?array $old, ?array $new): void
    {
        $item->activities()->create([
            'user_id' => $actor?->id,
            'event' => $event,
            'old' => $old,
            'new' => $new,
        ]);
    }
}
