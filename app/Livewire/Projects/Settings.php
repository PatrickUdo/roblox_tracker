<?php

namespace App\Livewire\Projects;

use App\Actions\Labels\CreateLabel;
use App\Actions\Labels\DeleteLabel;
use App\Actions\Projects\DeleteProject;
use App\Actions\Projects\RemoveProjectMember;
use App\Actions\Projects\SetProjectMember;
use App\Actions\Projects\UpdateProject;
use App\Actions\Webhooks\CreateWebhook;
use App\Actions\Webhooks\DeleteWebhook;
use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Settings extends Component
{
    public Project $project;

    public string $name = '';

    public string $description = '';

    public string $memberEmail = '';

    public string $memberRole = 'member';

    public string $labelName = '';

    public string $labelColor = '#3b82f6';

    public string $webhookUrl = '';

    /** Shown once, right after a webhook is created. */
    public ?string $newWebhookSecret = null;

    public string $confirmKey = '';

    public function mount(Project $project): void
    {
        $this->authorize('manage', $project);

        $this->name = $project->name;
        $this->description = (string) $project->description;
    }

    public function saveGeneral(UpdateProject $update): void
    {
        $update->handle($this->user(), $this->project, [
            'name' => $this->name,
            'description' => $this->description !== '' ? $this->description : null,
        ]);

        Flux::toast(variant: 'success', text: 'Project opgeslagen.');
    }

    public function addMember(SetProjectMember $set): void
    {
        $this->validate(['memberEmail' => ['required', 'email']]);

        $member = User::where('email', $this->memberEmail)->first();

        if ($member === null) {
            throw ValidationException::withMessages(['memberEmail' => 'Er is geen account met dit e-mailadres. De persoon moet zich eerst registreren.']);
        }

        $set->handle($this->user(), $this->project, $member, ProjectRole::from($this->memberRole));
        $this->reset(['memberEmail', 'memberRole']);
        Flux::toast(variant: 'success', text: "{$member->name} is toegevoegd.");
    }

    public function changeRole(int $userId, string $role, SetProjectMember $set): void
    {
        try {
            $set->handle($this->user(), $this->project, User::findOrFail($userId), ProjectRole::from($role));
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: $e->validator->errors()->first());
        }
    }

    public function removeMember(int $userId, RemoveProjectMember $remove): void
    {
        try {
            $remove->handle($this->user(), $this->project, User::findOrFail($userId));
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: $e->validator->errors()->first());

            return;
        }

        if ($userId === $this->user()->id) {
            $this->redirectRoute('projects.index', navigate: true);
        }
    }

    public function addLabel(CreateLabel $create): void
    {
        try {
            $create->handle($this->user(), $this->project, ['name' => $this->labelName, 'color' => $this->labelColor]);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages([
                'labelName' => $e->validator->errors()->first('name'),
                'labelColor' => $e->validator->errors()->first('color'),
            ]);
        }

        $this->reset('labelName');
    }

    public function deleteLabel(int $labelId, DeleteLabel $delete): void
    {
        $delete->handle($this->user(), $this->project->labels()->findOrFail($labelId));
    }

    public function addWebhook(CreateWebhook $create): void
    {
        try {
            $webhook = $create->handle($this->user(), $this->project, ['url' => $this->webhookUrl]);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['webhookUrl' => $e->validator->errors()->first('url')]);
        }

        $this->newWebhookSecret = $webhook->secret;
        $this->reset('webhookUrl');
    }

    public function deleteWebhook(int $webhookId, DeleteWebhook $delete): void
    {
        $delete->handle($this->user(), $this->project->webhooks()->findOrFail($webhookId));
    }

    public function deleteProject(DeleteProject $delete): void
    {
        if ($this->confirmKey !== $this->project->key) {
            throw ValidationException::withMessages(['confirmKey' => "Typ {$this->project->key} om te bevestigen."]);
        }

        $delete->handle($this->user(), $this->project);
        Flux::toast(text: "{$this->project->name} is verwijderd.");
        $this->redirectRoute('projects.index', navigate: true);
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    public function render(): View
    {
        return view('livewire.projects.settings', [
            'members' => $this->project->members()->orderBy('name')->get(),
            'labels' => $this->project->labels()->withCount('items')->orderBy('name')->get(),
            'webhooks' => $this->project->webhooks()->latest('id')->get(),
        ])->title($this->project->name.' · Instellingen');
    }
}
