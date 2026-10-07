@php
    $markdown = fn (?string $text) => \Illuminate\Support\Str::markdown((string) $text, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
@endphp

<div wire:poll.20s.visible>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item :href="route('projects.index')" wire:navigate>Projecten</flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('projects.board', $item->project)" wire:navigate>{{ $item->project->name }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $item->key }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="grid gap-8 lg:grid-cols-[1fr_18rem]">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="font-mono text-sm text-zinc-500">{{ $item->key }}</span>
                <x-item.type :type="$item->type" />
                <x-item.status :status="$item->status" />
                <x-item.priority :priority="$item->priority" />
            </div>

            <div class="mt-2 flex items-start justify-between gap-4">
                <flux:heading size="xl" level="1">{{ $item->title }}</flux:heading>

                @can('update', $item)
                    <div class="flex shrink-0 gap-1">
                        <flux:button size="sm" icon="pencil-square" wire:click="$dispatch('edit-item', { key: '{{ $item->key }}' })">Bewerken</flux:button>
                        @can('delete', $item)
                            <flux:modal.trigger name="delete-item">
                                <flux:button size="sm" variant="ghost" icon="trash" aria-label="Verwijderen" />
                            </flux:modal.trigger>
                        @endcan
                    </div>
                @endcan
            </div>

            <div class="prose prose-zinc mt-6 max-w-none dark:prose-invert">
                @if (filled($item->description))
                    {!! $markdown($item->description) !!}
                @else
                    <p class="text-zinc-400">Geen beschrijving.</p>
                @endif
            </div>

            @if ($item->inboxMessage)
                <details class="mt-6 rounded-lg border border-zinc-200 p-4 text-sm dark:border-zinc-700">
                    <summary class="cursor-pointer font-medium">Oorspronkelijke melding van de voice-agent</summary>
                    <p class="mt-3 whitespace-pre-line text-zinc-600 dark:text-zinc-300">{{ $item->inboxMessage->raw_text }}</p>
                    @if ($item->inboxMessage->reporter)
                        <p class="mt-2 text-zinc-500">Gemeld door {{ $item->inboxMessage->reporter }}</p>
                    @endif
                </details>
            @endif

            <flux:separator class="my-8" />

            <flux:heading size="lg">Reacties en activiteit</flux:heading>

            @php
                $timeline = $item->comments->map(fn ($comment) => ['type' => 'comment', 'at' => $comment->created_at, 'model' => $comment])
                    ->concat($item->activities->where('event', '!=', 'commented')->map(fn ($activity) => ['type' => 'activity', 'at' => $activity->created_at, 'model' => $activity]))
                    ->sortBy('at');
            @endphp

            <ol class="mt-4 space-y-4">
                @foreach ($timeline as $entry)
                    @if ($entry['type'] === 'comment')
                        <li wire:key="comment-{{ $entry['model']->id }}" class="rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <div class="flex items-center gap-2 border-b border-zinc-200 px-4 py-2 text-sm dark:border-zinc-700">
                                <flux:avatar size="xs" :initials="$entry['model']->user?->initials() ?? '?'" />
                                <span class="font-medium">{{ $entry['model']->user?->name ?? 'Onbekend' }}</span>
                                <span class="text-zinc-500" title="{{ $entry['at'] }}">{{ $entry['at']->diffForHumans() }}</span>
                            </div>
                            <div class="prose prose-sm prose-zinc max-w-none px-4 py-3 dark:prose-invert">
                                {!! $markdown($entry['model']->body) !!}
                            </div>
                        </li>
                    @else
                        <li wire:key="activity-{{ $entry['model']->id }}" class="flex items-center gap-2 pl-1 text-sm text-zinc-500">
                            <flux:icon.clock variant="micro" class="shrink-0 text-zinc-400" />
                            <span>
                                @include('livewire.items.activity', ['activity' => $entry['model']])
                                · <span title="{{ $entry['at'] }}">{{ $entry['at']->diffForHumans() }}</span>
                            </span>
                        </li>
                    @endif
                @endforeach
            </ol>

            @can('comment', $item)
                <form wire:submit="comment" class="mt-6 space-y-3">
                    <flux:textarea wire:model="body" placeholder="Schrijf een reactie (markdown)…" rows="3" />
                    <div class="flex justify-end">
                        <flux:button type="submit" variant="primary" size="sm">Reageren</flux:button>
                    </div>
                </form>
            @endcan
        </div>

        <aside class="space-y-6">
            @can('transition', $item)
                <div>
                    <flux:heading size="sm" class="mb-2">Status</flux:heading>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($item->status->allowedTransitions() as $next)
                            <flux:button size="sm" wire:click="changeStatus('{{ $next->value }}')" :variant="$next === \App\Enums\ItemStatus::Open ? 'ghost' : 'filled'">
                                {{ $next === \App\Enums\ItemStatus::Open ? 'Heropenen' : 'Naar '.strtolower($next->label()) }}
                            </flux:button>
                        @endforeach
                    </div>
                </div>
            @endcan

            <div>
                <flux:heading size="sm" class="mb-2">Toegewezen aan</flux:heading>
                @can('update', $item)
                    <flux:select size="sm" wire:change="assign($event.target.value)">
                        <flux:select.option value="" :selected="$item->assignee_id === null">Niemand</flux:select.option>
                        @foreach ($members as $member)
                            <flux:select.option :value="$member->id" :selected="$item->assignee_id === $member->id">{{ $member->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @else
                    <flux:text>{{ $item->assignee?->name ?? 'Niemand' }}</flux:text>
                @endcan
            </div>

            <div>
                <flux:heading size="sm" class="mb-2">Labels</flux:heading>
                @if ($item->labels->isNotEmpty())
                    <div class="flex flex-wrap gap-1">
                        @foreach ($item->labels as $itemLabel)
                            <x-item.label :label="$itemLabel" />
                        @endforeach
                    </div>
                @else
                    <flux:text>Geen</flux:text>
                @endif
            </div>

            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-2">
                    <dt class="text-zinc-500">Gemeld door</dt>
                    <dd>{{ $item->reporter?->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-zinc-500">Bron</dt>
                    <dd>{{ $item->source->label() }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-zinc-500">Aangemaakt</dt>
                    <dd title="{{ $item->created_at }}">{{ $item->created_at->diffForHumans() }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-zinc-500">Bijgewerkt</dt>
                    <dd title="{{ $item->updated_at }}">{{ $item->updated_at->diffForHumans() }}</dd>
                </div>
                @if ($item->closed_at)
                    <div class="flex justify-between gap-2">
                        <dt class="text-zinc-500">Gesloten</dt>
                        <dd title="{{ $item->closed_at }}">{{ $item->closed_at->diffForHumans() }}</dd>
                    </div>
                @endif
            </dl>
        </aside>
    </div>

    @can('delete', $item)
        <flux:modal name="delete-item" class="max-w-sm">
            <div class="space-y-5">
                <div>
                    <flux:heading size="lg">{{ $item->key }} verwijderen?</flux:heading>
                    <flux:text class="mt-2">Het item, de reacties en de activiteit worden definitief verwijderd.</flux:text>
                </div>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Annuleren</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="delete">Verwijderen</flux:button>
                </div>
            </div>
        </flux:modal>
    @endcan

    <livewire:items.item-form :project="$item->project" />
</div>
