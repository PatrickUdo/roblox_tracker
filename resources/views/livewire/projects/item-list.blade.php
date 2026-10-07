<div>
    <x-project-header :project="$project" current="list">
        <x-slot:actions>
            <flux:button wire:click="$dispatch('create-item', { type: 'bug' })" icon="bug-ant">Bug melden</flux:button>
            <flux:button wire:click="$dispatch('create-item', { type: 'feature' })" variant="primary" icon="plus">Nieuwe feature</flux:button>
        </x-slot:actions>
    </x-project-header>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <flux:input wire:model.live.debounce.300ms="q" size="sm" icon="magnifying-glass" placeholder="Zoeken op titel, tekst of key" class="w-full sm:w-72" clearable />

        <flux:select wire:model.live="status" size="sm" class="w-auto min-w-32">
            <flux:select.option value="">Alle statussen</flux:select.option>
            @foreach (\App\Enums\ItemStatus::cases() as $case)
                <flux:select.option :value="$case->value">{{ $case->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        @include('livewire.projects.filters')
    </div>

    <flux:table :paginate="$this->items">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sort === 'number'" :direction="$direction" wire:click="sortBy('number')" class="w-32">Key</flux:table.column>
            <flux:table.column sortable :sorted="$sort === 'title'" :direction="$direction" wire:click="sortBy('title')">Titel</flux:table.column>
            <flux:table.column>Type</flux:table.column>
            <flux:table.column sortable :sorted="$sort === 'status'" :direction="$direction" wire:click="sortBy('status')">Status</flux:table.column>
            <flux:table.column sortable :sorted="$sort === 'priority'" :direction="$direction" wire:click="sortBy('priority')">Prioriteit</flux:table.column>
            <flux:table.column>Toegewezen</flux:table.column>
            <flux:table.column sortable :sorted="$sort === 'updated_at'" :direction="$direction" wire:click="sortBy('updated_at')">Bijgewerkt</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->items as $item)
                <flux:table.row :key="$item->id">
                    <flux:table.cell class="font-mono text-xs">
                        <a href="{{ route('items.show', $item) }}" wire:navigate class="hover:underline">{{ $item->key }}</a>
                    </flux:table.cell>
                    <flux:table.cell class="max-w-md">
                        <a href="{{ route('items.show', $item) }}" wire:navigate class="block truncate font-medium text-zinc-800 hover:underline dark:text-white">{{ $item->title }}</a>
                        @if ($item->labels->isNotEmpty())
                            <div class="mt-1 flex flex-wrap gap-1">
                                @foreach ($item->labels as $itemLabel)
                                    <x-item.label :label="$itemLabel" />
                                @endforeach
                            </div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell><x-item.type :type="$item->type" /></flux:table.cell>
                    <flux:table.cell><x-item.status :status="$item->status" /></flux:table.cell>
                    <flux:table.cell><x-item.priority :priority="$item->priority" /></flux:table.cell>
                    <flux:table.cell>{{ $item->assignee?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap text-zinc-500" title="{{ $item->updated_at }}">{{ $item->updated_at->diffForHumans() }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="py-10 text-center text-zinc-500">Geen items gevonden.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:items.item-form :project="$project" />
</div>
