<?php

namespace App\Livewire\Items;

use App\Actions\Comments\AddComment;
use App\Actions\Items\DeleteItem;
use App\Actions\Items\TransitionItem;
use App\Actions\Items\UpdateItem;
use App\Models\Item;
use App\Models\User;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public Item $item;

    public string $body = '';

    public function mount(Item $item): void
    {
        $this->authorize('view', $item);
    }

    public function changeStatus(string $status, TransitionItem $transition): void
    {
        try {
            $transition->handle($this->user(), $this->item, $status);
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: $e->validator->errors()->first());
        }
    }

    public function assign(string $userId, UpdateItem $update): void
    {
        $update->handle($this->user(), $this->item, ['assignee_id' => $userId !== '' ? (int) $userId : null]);
    }

    public function comment(AddComment $add): void
    {
        $add->handle($this->user(), $this->item, ['body' => $this->body]);
        $this->reset('body');
    }

    public function delete(DeleteItem $delete): void
    {
        $project = $this->item->project;
        $delete->handle($this->user(), $this->item);

        Flux::toast(text: "{$this->item->key} verwijderd.");
        $this->redirectRoute('projects.board', $project, navigate: true);
    }

    #[On('item-saved')]
    public function refreshItem(): void
    {
        $this->item->refresh();
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    public function render(): View
    {
        $this->item->load(['project', 'reporter', 'assignee', 'labels', 'comments.user', 'activities.user', 'inboxMessage']);

        return view('livewire.items.show', [
            'members' => $this->item->project->members()->orderBy('name')->get(),
        ])->title("{$this->item->key} {$this->item->title}");
    }
}
