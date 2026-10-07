<?php

namespace App\Events;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class CommentAdded implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public Comment $comment,
        public ?User $actor,
    ) {}
}
