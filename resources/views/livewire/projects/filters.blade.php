{{-- Shared filter bar for the board and the list. Expects $members and $labels. --}}
<div class="flex flex-wrap items-center gap-2">
    <flux:select wire:model.live="type" size="sm" class="w-auto min-w-32" placeholder="Alle types">
        <flux:select.option value="">Alle types</flux:select.option>
        @foreach (\App\Enums\ItemType::cases() as $case)
            <flux:select.option :value="$case->value">{{ $case->label() }}</flux:select.option>
        @endforeach
    </flux:select>

    <flux:select wire:model.live="priority" size="sm" class="w-auto min-w-36">
        <flux:select.option value="">Alle prioriteiten</flux:select.option>
        @foreach (\App\Enums\ItemPriority::cases() as $case)
            <flux:select.option :value="$case->value">{{ $case->label() }}</flux:select.option>
        @endforeach
    </flux:select>

    @if ($labels->isNotEmpty())
        <flux:select wire:model.live="label" size="sm" class="w-auto min-w-32">
            <flux:select.option value="">Alle labels</flux:select.option>
            @foreach ($labels as $projectLabel)
                <flux:select.option :value="$projectLabel->name">{{ $projectLabel->name }}</flux:select.option>
            @endforeach
        </flux:select>
    @endif

    <flux:select wire:model.live="assignee" size="sm" class="w-auto min-w-40">
        <flux:select.option value="">Iedereen</flux:select.option>
        <flux:select.option value="me">Aan mij toegewezen</flux:select.option>
        <flux:select.option value="none">Niet toegewezen</flux:select.option>
        @foreach ($members as $member)
            <flux:select.option :value="$member->id">{{ $member->name }}</flux:select.option>
        @endforeach
    </flux:select>

    @if ($type || $priority || $label || $assignee)
        <flux:button wire:click="clearFilters" size="sm" variant="ghost" icon="x-mark">Wissen</flux:button>
    @endif
</div>
