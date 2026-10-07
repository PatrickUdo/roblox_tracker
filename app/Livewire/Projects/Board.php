<?php

namespace App\Livewire\Projects;

use App\Actions\Items\TransitionItem;
use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use App\Queries\SearchItems;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Board extends Component
{
    /** Closed items older than this drop off the board; the list shows them all. */
    private const CLOSED_DAYS = 14;

    public Project $project;

    #[Url]
    public string $type = '';

    #[Url]
    public string $priority = '';

    #[Url]
    public string $label = '';

    #[Url]
    public string $assignee = '';

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);
    }

    /**
     * @return Collection<string, \Illuminate\Database\Eloquent\Collection<int, Item>>
     */
    #[Computed]
    public function columns(): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        $items = app(SearchItems::class)
            ->query($user, $this->project, [
                'type' => $this->type,
                'priority' => $this->priority,
                'label' => $this->label,
                'assignee' => $this->assignee,
                'sort' => 'priority',
                'direction' => 'desc',
            ])
            ->where(fn ($query) => $query
                ->whereNot('status', ItemStatus::Closed)
                ->orWhere('closed_at', '>=', now()->subDays(self::CLOSED_DAYS)))
            ->get();

        return collect(ItemStatus::cases())->mapWithKeys(fn (ItemStatus $status) => [
            $status->value => $items->where('status', $status)->values(),
        ]);
    }

    public function moveItem(string $key, string $status, TransitionItem $transition): void
    {
        $item = Item::findByKeyOrFail($key);
        abort_unless($item->project_id === $this->project->id, 404);

        /** @var User $user */
        $user = auth()->user();

        try {
            $transition->handle($user, $item, $status);
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: $e->validator->errors()->first());
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['type', 'priority', 'label', 'assignee']);
    }

    #[On('item-saved')]
    public function refreshBoard(): void
    {
        unset($this->columns);
    }

    public function render(): View
    {
        return view('livewire.projects.board', [
            'members' => $this->project->members()->orderBy('name')->get(),
            'labels' => $this->project->labels()->orderBy('name')->get(),
        ])->title($this->project->name);
    }
}
