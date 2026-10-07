<div>
    <flux:modal name="item-form" class="w-full max-w-2xl">
        <form wire:submit="save" class="space-y-5">
            <flux:heading size="lg">{{ $itemKey ? "{$itemKey} bewerken" : 'Nieuw item' }}</flux:heading>

            <flux:radio.group wire:model="type" label="Type" variant="segmented">
                @foreach (\App\Enums\ItemType::cases() as $case)
                    <flux:radio :value="$case->value" :label="$case->label()" :icon="$case->icon()" />
                @endforeach
            </flux:radio.group>

            <flux:input wire:model="title" label="Titel" required autofocus />

            <flux:textarea wire:model="description" label="Beschrijving" description="Markdown wordt ondersteund." rows="8" class:input="font-mono text-sm" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="priority" label="Prioriteit">
                    @foreach (\App\Enums\ItemPriority::cases() as $case)
                        <flux:select.option :value="$case->value">{{ $case->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="assignee_id" label="Toegewezen aan">
                    <flux:select.option value="">Niemand</flux:select.option>
                    @foreach ($members as $member)
                        <flux:select.option :value="$member->id">{{ $member->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            @if ($projectLabels->isNotEmpty())
                <flux:checkbox.group wire:model="labels" label="Labels" class="flex flex-wrap gap-x-5 gap-y-2 *:!mt-0">
                    @foreach ($projectLabels as $label)
                        <flux:checkbox :value="$label->name" :label="$label->name" />
                    @endforeach
                </flux:checkbox.group>
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Annuleren</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ $itemKey ? 'Opslaan' : 'Aanmaken' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
