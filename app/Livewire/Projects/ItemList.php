<?php

namespace App\Livewire\Projects;

use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use App\Queries\SearchItems;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class ItemList extends Component
{
    use WithPagination;

    public Project $project;

    #[Url]
    public string $q = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $priority = '';

    #[Url]
    public string $label = '';

    #[Url]
    public string $assignee = '';

    #[Url]
    public string $sort = 'number';

    #[Url]
    public string $direction = 'desc';

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);
    }

    public function updated(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, SearchItems::SORTS, true)) {
            return;
        }

        $this->direction = $this->sort === $column && $this->direction === 'desc' ? 'asc' : 'desc';
        $this->sort = $column;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['q', 'type', 'status', 'priority', 'label', 'assignee']);
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Item>
     */
    #[Computed]
    public function items(): LengthAwarePaginator
    {
        /** @var User $user */
        $user = auth()->user();

        return app(SearchItems::class)
            ->query($user, $this->project, [
                'q' => $this->q,
                'type' => $this->type,
                'status' => $this->status !== '' ? [$this->status] : null,
                'priority' => $this->priority,
                'label' => $this->label,
                'assignee' => $this->assignee,
                'sort' => $this->sort,
                'direction' => $this->direction,
            ])
            ->paginate(25);
    }

    #[On('item-saved')]
    public function refreshList(): void
    {
        unset($this->items);
    }

    public function render(): View
    {
        return view('livewire.projects.item-list', [
            'members' => $this->project->members()->orderBy('name')->get(),
            'labels' => $this->project->labels()->orderBy('name')->get(),
        ])->title($this->project->name.' · Lijst');
    }
}
