<?php

namespace App\Jobs;

use App\Actions\Inbox\ConvertInboxMessage;
use App\Enums\InboxStatus;
use App\Inbox\InboxInterpreter;
use App\Inbox\InterpretationFailed;
use App\Models\InboxMessage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProcessInboxMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60];

    public function __construct(public InboxMessage $message) {}

    public function handle(InboxInterpreter $interpreter, ConvertInboxMessage $convert): void
    {
        $message = $this->message->fresh();

        if ($message === null || $message->status !== InboxStatus::Pending) {
            return;
        }

        try {
            $suggestion = $interpreter->interpret($message->raw_text);
        } catch (InterpretationFailed $e) {
            $message->update(['status' => InboxStatus::Failed, 'error' => $e->getMessage()]);

            return;
        }

        $message->update(['status' => InboxStatus::Draft, 'suggestion' => $suggestion->toArray(), 'error' => null]);

        if ($suggestion->confidence < (float) config('inbox.threshold') || $message->user === null) {
            return;
        }

        try {
            $convert->handle($message->user, $message);
        } catch (ValidationException|AuthorizationException $e) {
            // Leave it as a draft; someone can fix it up in the inbox.
            $message->update(['error' => $e->getMessage()]);
        }
    }

    public function failed(?Throwable $e): void
    {
        $this->message->update([
            'status' => InboxStatus::Failed,
            'error' => $e?->getMessage() ?? 'Processing failed.',
        ]);
    }
}
