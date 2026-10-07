<?php

namespace App\Livewire\Items;

use App\Actions\Items\CreateItem;
use App\Actions\Items\UpdateItem;
use App\Enums\ItemSource;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Create/edit modal shared by the board, list and item pages. Open it by
 * dispatching `create-item` or `edit-item` (with the item key).
 */
class ItemForm extends Component
{
    #[Locked]
    public Project $project;

    #[Locked]
    public ?string $itemKey = null;

    public string $type = 'bug';

    public string $title = '';

    public string $description = '';

    public string $priority = 'medium';

    public string $assignee_id = '';

    /** @var list<string> */
    public array $labels = [];

    #[On('create-item')]
    public function create(string $type = 'bug'): void
    {
        $this->resetErrorBag();
        $this->reset(['itemKey', 'title', 'description', 'priority', 'assignee_id', 'labels']);
        $this->type = $type;

        Flux::modal('item-form')->show();
    }

    #[On('edit-item')]
    public function edit(string $key): void
    {
        $item = Item::findByKeyOrFail($key);
        $this->authorize('update', $item);

        $this->resetErrorBag();
        $this->itemKey = $item->key;
        $this->type = $item->type->value;
        $this->title = $item->title;
        $this->description = (string) $item->description;
        $this->priority = $item->priority->value;
        $this->assignee_id = (string) ($item->assignee_id ?? '');
        $this->labels = $item->labels()->pluck('name')->all();

        Flux::modal('item-form')->show();
    }

    public function save(CreateItem $create, UpdateItem $update): void
    {
        /** @var User $user */
        $user = auth()->user();

        $data = [
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description !== '' ? $this->description : null,
            'priority' => $this->priority,
            'assignee_id' => $this->assignee_id !== '' ? (int) $this->assignee_id : null,
            'labels' => $this->labels,
        ];

        if ($this->itemKey !== null) {
            $item = $update->handle($user, Item::findByKeyOrFail($this->itemKey), $data);
            Flux::toast(variant: 'success', text: "{$item->key} bijgewerkt.");
        } else {
            $item = $create->handle($user, $this->project, $data, ItemSource::Web);
            Flux::toast(variant: 'success', heading: "{$item->key} aangemaakt", text: $item->title);
        }

        Flux::modal('item-form')->close();
        $this->dispatch('item-saved', key: $item->key);
    }

    public function render(): View
    {
        return view('livewire.items.item-form', [
            'members' => $this->project->members()->orderBy('name')->get(),
            'projectLabels' => $this->project->labels()->orderBy('name')->get(),
        ]);
    }
}
