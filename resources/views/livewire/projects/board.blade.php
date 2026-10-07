<div wire:poll.15s.visible>
    <x-project-header :project="$project" current="board">
        <x-slot:actions>
            <flux:button wire:click="$dispatch('create-item', { type: 'bug' })" icon="bug-ant">Bug melden</flux:button>
            <flux:button wire:click="$dispatch('create-item', { type: 'feature' })" variant="primary" icon="plus">Nieuwe feature</flux:button>
        </x-slot:actions>
    </x-project-header>

    <div class="mb-4">
        @include('livewire.projects.filters')
    </div>

    {{-- Native drag and drop: cards carry their allowed target statuses, other columns dim while dragging. --}}
    <div
        x-data="{ dragging: null, allowed: [] }"
        class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"
    >
        @foreach (\App\Enums\ItemStatus::cases() as $status)
            @php($items = $this->columns[$status->value])
            <section
                wire:key="column-{{ $status->value }}"
                x-on:dragover="if (allowed.includes('{{ $status->value }}')) { $event.preventDefault(); $event.dataTransfer.dropEffect = 'move' }"
                x-on:drop.prevent="if (dragging && allowed.includes('{{ $status->value }}')) { $wire.moveItem(dragging, '{{ $status->value }}') } dragging = null"
                :class="{
                    'opacity-40': dragging && !allowed.includes('{{ $status->value }}') && !$el.querySelector('[data-key=\'' + dragging + '\']'),
                    'ring-2 ring-blue-400/60': dragging && allowed.includes('{{ $status->value }}'),
                }"
                class="flex min-h-48 flex-col rounded-xl bg-zinc-100 p-2 transition dark:bg-zinc-900"
            >
                <header class="flex items-center justify-between px-2 pb-2 pt-1">
                    <div class="flex items-center gap-2">
                        <x-item.status :status="$status" />
                        <span class="text-sm text-zinc-500">{{ $items->count() }}</span>
                    </div>
                    @if ($status === \App\Enums\ItemStatus::Closed)
                        <flux:tooltip content="Afgelopen 14 dagen">
                            <flux:icon.information-circle variant="micro" class="text-zinc-400" />
                        </flux:tooltip>
                    @endif
                </header>

                <div class="flex flex-1 flex-col gap-2">
                    @forelse ($items as $item)
                        <a
                            href="{{ route('items.show', $item) }}"
                            wire:navigate
                            wire:key="card-{{ $item->id }}"
                            data-key="{{ $item->key }}"
                            draggable="true"
                            x-on:dragstart="dragging = '{{ $item->key }}'; allowed = @js(array_column($item->status->allowedTransitions(), 'value')); $event.dataTransfer.effectAllowed = 'move'"
                            x-on:dragend="dragging = null; allowed = []"
                            :class="dragging === '{{ $item->key }}' && 'opacity-50'"
                            class="block cursor-grab rounded-lg border border-zinc-200 bg-white p-3 shadow-xs transition hover:border-zinc-300 active:cursor-grabbing dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-zinc-600"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <span class="flex items-center gap-1.5 font-mono text-xs text-zinc-500">
                                    <flux:icon :name="$item->type->icon()" variant="micro" @class(['text-red-500' => $item->type === \App\Enums\ItemType::Bug, 'text-violet-500' => $item->type === \App\Enums\ItemType::Feature]) />
                                    {{ $item->key }}
                                </span>
                                <x-item.priority :priority="$item->priority" />
                            </div>

                            <p class="mt-1.5 text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $item->title }}</p>

                            @if ($item->labels->isNotEmpty() || $item->assignee)
                                <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($item->labels as $itemLabel)
                                            <x-item.label :label="$itemLabel" />
                                        @endforeach
                                    </div>
                                    @if ($item->assignee)
                                        <flux:tooltip :content="$item->assignee->name">
                                            <flux:avatar size="xs" :initials="$item->assignee->initials()" />
                                        </flux:tooltip>
                                    @endif
                                </div>
                            @endif
                        </a>
                    @empty
                        <p class="px-2 py-6 text-center text-sm text-zinc-400">Geen items</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    <livewire:items.item-form :project="$project" />
</div>
