<?php

namespace App\Http\Controllers\Api;

use App\Actions\Inbox\SubmitInboxMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreInboxMessageRequest;
use App\Http\Resources\InboxMessageResource;
use App\Models\InboxMessage;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class InboxController extends Controller
{
    /**
     * Submit free text
     *
     * For the voice agent: the transcription is turned into an item in the background. Responds
     * immediately with 202 and an `inbox_id`; poll `GET /inbox/{id}` for the outcome. Requires `inbox:write`.
     *
     * @status 202
     */
    public function store(StoreInboxMessageRequest $request, SubmitInboxMessage $submit): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $project = Project::findBySlugOrKeyOrFail((string) $request->validated('project'));

        $message = $submit->handle($user, $project, $request->safe()->only(['text', 'reporter']));

        return InboxMessageResource::make($message->load('project'))
            ->response()
            ->setStatusCode(202);
    }

    /**
     * Show an inbox message
     *
     * Status is `pending`, `draft` (waiting for review), `converted` (see `item_key`), `discarded` or `failed`.
     */
    public function show(InboxMessage $inboxMessage): InboxMessageResource
    {
        Gate::authorize('view', $inboxMessage);

        return InboxMessageResource::make($inboxMessage->load(['project', 'item.project']));
    }
}
