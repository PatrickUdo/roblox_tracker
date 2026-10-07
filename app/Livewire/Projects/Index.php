<?php

namespace App\Livewire\Projects;

use App\Actions\Projects\CreateProject;
use App\Enums\InboxStatus;
use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Models\Project;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Projecten')]
class Index extends Component
{
    public string $name = '';

    public string $key = '';

    public string $description = '';

    /** Stop suggesting a key from the name once the user typed one. */
    public bool $keyEdited = false;

    /**
     * @return Collection<int, Project>
     */
    #[Computed]
    public function projects(): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        return Project::visibleTo($user)
            ->withCount([
                'items as open_bugs_count' => fn (Builder $q) => $q->where('type', ItemType::Bug)->whereIn('status', [ItemStatus::Open, ItemStatus::InProgress]),
                'items as open_features_count' => fn (Builder $q) => $q->where('type', ItemType::Feature)->whereIn('status', [ItemStatus::Open, ItemStatus::InProgress]),
                'inboxMessages as inbox_count' => fn (Builder $q) => $q->whereIn('status', [InboxStatus::Pending, InboxStatus::Draft, InboxStatus::Failed]),
            ])
            ->orderBy('name')
            ->get();
    }

    public function updatedName(string $value): void
    {
        if (! $this->keyEdited) {
            $this->key = Str::of($value)->ascii()->upper()->replaceMatches('/[^A-Z0-9]/', '')->limit(10, '')->value();
        }
    }

    public function updatedKey(): void
    {
        $this->keyEdited = true;
    }

    public function create(CreateProject $create): void
    {
        /** @var User $user */
        $user = auth()->user();

        $project = $create->handle($user, [
            'name' => $this->name,
            'key' => $this->key,
            'description' => $this->description ?: null,
        ]);

        Flux::modal('create-project')->close();
        $this->redirectRoute('projects.board', $project, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.projects.index');
    }
}
