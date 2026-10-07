<?php

namespace App\Actions\Comments;

use App\Events\CommentAdded;
use App\Models\Comment;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class AddComment
{
    /**
     * @param  array<string, mixed>  $data  body
     */
    public function handle(User $actor, Item $item, array $data): Comment
    {
        Gate::forUser($actor)->authorize('comment', $item);

        $validated = Validator::make($data, [
            'body' => ['required', 'string', 'max:65535'],
        ])->validate();

        return DB::transaction(function () use ($actor, $item, $validated) {
            $comment = $item->comments()->create([
                'user_id' => $actor->id,
                'body' => $validated['body'],
            ]);

            CommentAdded::dispatch($comment, $actor);

            return $comment;
        });
    }
}
