<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Projecten</flux:heading>
            <flux:text class="mt-1">Bugs en features per project.</flux:text>
        </div>

        <flux:modal.trigger name="create-project">
            <flux:button variant="primary" icon="plus">Nieuw project</flux:button>
        </flux:modal.trigger>
    </div>

    @if ($this->projects->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
            <flux:icon.folder-plus class="mx-auto size-10 text-zinc-400" />
            <flux:heading class="mt-4">Nog geen projecten</flux:heading>
            <flux:text class="mt-1">Maak een project aan, of vraag een eigenaar om je toe te voegen aan een bestaand project.</flux:text>
            <flux:modal.trigger name="create-project">
                <flux:button class="mt-4" icon="plus">Nieuw project</flux:button>
            </flux:modal.trigger>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($this->projects as $project)
                <a href="{{ route('projects.board', $project) }}" wire:navigate wire:key="project-{{ $project->id }}"
                   class="group rounded-xl border border-zinc-200 bg-white p-5 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <flux:heading class="truncate group-hover:underline">{{ $project->name }}</flux:heading>
                            <flux:text size="sm" class="mt-0.5 font-mono">{{ $project->key }}</flux:text>
                        </div>
                        @if ($project->inbox_count > 0)
                            <flux:badge size="sm" color="amber" icon="inbox">{{ $project->inbox_count }}</flux:badge>
                        @endif
                    </div>

                    @if ($project->description)
                        <flux:text class="mt-3 line-clamp-2">{{ $project->description }}</flux:text>
                    @endif

                    <div class="mt-4 flex gap-4 text-sm">
                        <span class="flex items-center gap-1.5 text-zinc-600 dark:text-zinc-300">
                            <flux:icon.bug-ant variant="micro" class="text-red-500" />
                            {{ $project->open_bugs_count }} open {{ $project->open_bugs_count === 1 ? 'bug' : 'bugs' }}
                        </span>
                        <span class="flex items-center gap-1.5 text-zinc-600 dark:text-zinc-300">
                            <flux:icon.sparkles variant="micro" class="text-violet-500" />
                            {{ $project->open_features_count }} open {{ $project->open_features_count === 1 ? 'feature' : 'features' }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    <flux:modal name="create-project" class="w-full max-w-md">
        <form wire:submit="create" class="space-y-5">
            <div>
                <flux:heading size="lg">Nieuw project</flux:heading>
                <flux:text class="mt-1">Je wordt eigenaar van het project.</flux:text>
            </div>

            <flux:input wire:model.live.debounce.300ms="name" label="Naam" required autofocus />
            <flux:input wire:model.blur="key" label="Key" description="Prefix voor itemnummers, zoals BONKBOX-42. Hoofdletters en cijfers, 2 tot 10 tekens. Kan later niet meer worden gewijzigd." class:input="font-mono uppercase" required maxlength="10" />
            <flux:textarea wire:model="description" label="Beschrijving" rows="3" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Annuleren</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Aanmaken</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
