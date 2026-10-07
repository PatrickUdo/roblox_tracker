<?php

namespace App\Http\Controllers\Api;

use App\Actions\Comments\AddComment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    /**
     * List comments on an item
     */
    public function index(Item $item): AnonymousResourceCollection
    {
        Gate::authorize('view', $item);

        return CommentResource::collection($item->comments()->with('user')->oldest('id')->cursorPaginate(50));
    }

    /**
     * Add a comment
     */
    public function store(StoreCommentRequest $request, Item $item, AddComment $add): CommentResource
    {
        /** @var User $user */
        $user = $request->user();

        return CommentResource::make($add->handle($user, $item, $request->validated())->load('user'));
    }
}
