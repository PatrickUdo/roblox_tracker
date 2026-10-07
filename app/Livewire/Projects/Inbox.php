<?php

namespace App\Livewire\Projects;

use App\Actions\Inbox\ConvertInboxMessage;
use App\Actions\Inbox\DiscardInboxMessage;
use App\Actions\Inbox\RetryInboxMessage;
use App\Actions\Inbox\SubmitInboxMessage;
use App\Enums\InboxStatus;
use App\Models\InboxMessage;
use App\Models\Project;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Inbox extends Component
{
    public Project $project;

    /** Text typed in by hand, for testing without the voice agent. */
    public string $text = '';

    public string $reporter = '';

    #[Locked]
    public ?int $convertingId = null;

    public string $type = 'bug';

    public string $title = '';

    public string $description = '';

    public string $priority = 'medium';

    public function mount(Project $project): void
    {
        $this->authorize('viewInbox', $project);
    }

    /**
     * @return Collection<int, InboxMessage>
     */
    #[Computed]
    public function open(): Collection
    {
        return $this->project->inboxMessages()
            ->with('user')
            ->whereIn('status', [InboxStatus::Pending, InboxStatus::Draft, InboxStatus::Failed])
            ->latest('id')
            ->get();
    }

    /**
     * @return Collection<int, InboxMessage>
     */
    #[Computed]
    public function handled(): Collection
    {
        return $this->project->inboxMessages()
            ->with(['user', 'item.project'])
            ->whereIn('status', [InboxStatus::Converted, InboxStatus::Discarded])
            ->latest('updated_at')
            ->limit(20)
            ->get();
    }

    public function submit(SubmitInboxMessage $submit): void
    {
        $submit->handle($this->user(), $this->project, [
            'text' => $this->text,
            'reporter' => $this->reporter !== '' ? $this->reporter : null,
        ]);

        $this->reset(['text', 'reporter']);
        Flux::modal('submit-text')->close();
        Flux::toast(text: 'Bericht ontvangen; het wordt nu verwerkt.');
    }

    public function startConvert(int $id): void
    {
        $message = $this->message($id);
        $suggestion = $message->suggestion ?? [];

        $this->resetErrorBag();
        $this->convertingId = $message->id;
        $this->type = $suggestion['type'] ?? 'bug';
        $this->title = $suggestion['title'] ?? '';
        $this->description = $suggestion['description'] ?? $message->raw_text;
        $this->priority = $suggestion['priority'] ?? 'medium';

        Flux::modal('convert')->show();
    }

    public function convert(ConvertInboxMessage $convert): void
    {
        abort_if($this->convertingId === null, 404);

        $item = $convert->handle($this->user(), $this->message($this->convertingId), [
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
        ]);

        $this->convertingId = null;
        Flux::modal('convert')->close();
        Flux::toast(variant: 'success', heading: "{$item->key} aangemaakt", text: $item->title);
    }

    public function discard(int $id, DiscardInboxMessage $discard): void
    {
        $discard->handle($this->user(), $this->message($id));
    }

    public function retry(int $id, RetryInboxMessage $retry): void
    {
        $retry->handle($this->user(), $this->message($id));
    }

    private function message(int $id): InboxMessage
    {
        return $this->project->inboxMessages()->findOrFail($id);
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    public function render(): View
    {
        return view('livewire.projects.inbox', [
            'threshold' => (float) config('inbox.threshold'),
        ])->title($this->project->name.' · Inbox');
    }
}
