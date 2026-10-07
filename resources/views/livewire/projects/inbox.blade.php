<div @if ($this->open->contains('status', \App\Enums\InboxStatus::Pending)) wire:poll.3s @endif>
    <x-project-header :project="$project" current="inbox">
        <x-slot:actions>
            @can('submitInbox', $project)
                <flux:modal.trigger name="submit-text">
                    <flux:button icon="chat-bubble-bottom-center-text">Tekst invoeren</flux:button>
                </flux:modal.trigger>
            @endcan
        </x-slot:actions>
    </x-project-header>

    <flux:text class="mb-6 max-w-3xl">
        Meldingen van de voice-agent worden door Claude omgezet naar een item. Bij een zekerheid van
        {{ number_format($threshold * 100) }}% of meer gebeurt dat automatisch; daaronder staan ze hier als concept.
    </flux:text>

    @if ($this->open->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
            <flux:icon.inbox class="mx-auto size-10 text-zinc-400" />
            <flux:heading class="mt-4">De inbox is leeg</flux:heading>
            <flux:text class="mt-1">Nieuwe meldingen die beoordeling nodig hebben verschijnen hier.</flux:text>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($this->open as $message)
                <article wire:key="inbox-{{ $message->id }}" class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2 text-sm text-zinc-500">
                            <flux:badge size="sm" :color="$message->status->color()">
                                @if ($message->status === \App\Enums\InboxStatus::Pending)
                                    <flux:icon.loading variant="micro" class="mr-1" />
                                @endif
                                {{ $message->status->label() }}
                            </flux:badge>
                            <span>{{ $message->reporter ?? $message->user?->name ?? 'Onbekend' }}</span>
                            <span>·</span>
                            <span title="{{ $message->created_at }}">{{ $message->created_at->diffForHumans() }}</span>
                        </div>

                        @can('reviewInbox', $project)
                            <div class="flex gap-2">
                                @if ($message->status === \App\Enums\InboxStatus::Failed)
                                    <flux:button size="sm" icon="arrow-path" wire:click="retry({{ $message->id }})">Opnieuw</flux:button>
                                @endif
                                @if ($message->status !== \App\Enums\InboxStatus::Pending)
                                    <flux:button size="sm" variant="ghost" wire:click="discard({{ $message->id }})" wire:confirm="Dit bericht weggooien?">Weggooien</flux:button>
                                    <flux:button size="sm" variant="primary" wire:click="startConvert({{ $message->id }})">Omzetten naar item</flux:button>
                                @endif
                            </div>
                        @endcan
                    </div>

                    <blockquote class="mt-3 whitespace-pre-line border-l-2 border-zinc-200 pl-3 text-sm text-zinc-700 dark:border-zinc-700 dark:text-zinc-300">{{ $message->raw_text }}</blockquote>

                    @if ($message->suggestion)
                        <div class="mt-4 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                            <div class="flex flex-wrap items-center gap-2">
                                <flux:text size="sm" class="font-medium">Voorstel</flux:text>
                                @if ($type = \App\Enums\ItemType::tryFrom($message->suggestion['type'] ?? ''))
                                    <x-item.type :type="$type" />
                                @endif
                                @if ($priority = \App\Enums\ItemPriority::tryFrom($message->suggestion['priority'] ?? ''))
                                    <x-item.priority :priority="$priority" />
                                @endif
                                <flux:badge size="sm" :color="($message->suggestion['confidence'] ?? 0) >= $threshold ? 'green' : 'amber'">
                                    {{ number_format(($message->suggestion['confidence'] ?? 0) * 100) }}% zeker
                                </flux:badge>
                            </div>
                            <p class="mt-2 font-medium">{{ $message->suggestion['title'] ?? '' }}</p>
                        </div>
                    @endif

                    @if ($message->error)
                        <flux:text size="sm" class="mt-3 text-red-600 dark:text-red-400">{{ $message->error }}</flux:text>
                    @endif
                </article>
            @endforeach
        </div>
    @endif

    @if ($this->handled->isNotEmpty())
        <flux:heading size="lg" class="mb-3 mt-10">Recent afgehandeld</flux:heading>
        <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @foreach ($this->handled as $message)
                <div wire:key="handled-{{ $message->id }}" class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-zinc-700 dark:text-zinc-300">{{ $message->raw_text }}</p>
                        <p class="text-zinc-500">{{ $message->reporter ?? $message->user?->name }} · {{ $message->updated_at->diffForHumans() }}</p>
                    </div>
                    @if ($message->item)
                        <a href="{{ route('items.show', $message->item) }}" wire:navigate class="font-mono text-sm hover:underline">{{ $message->item->key }}</a>
                    @else
                        <flux:badge size="sm" :color="$message->status->color()">{{ $message->status->label() }}</flux:badge>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <flux:modal name="submit-text" class="w-full max-w-lg">
        <form wire:submit="submit" class="space-y-5">
            <div>
                <flux:heading size="lg">Tekst invoeren</flux:heading>
                <flux:text class="mt-1">Gaat door dezelfde verwerking als berichten van de voice-agent.</flux:text>
            </div>
            <flux:textarea wire:model="text" label="Melding" rows="6" required />
            <flux:input wire:model="reporter" label="Gemeld door" placeholder="Optioneel" />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Annuleren</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Versturen</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="convert" class="w-full max-w-2xl">
        <form wire:submit="convert" class="space-y-5">
            <flux:heading size="lg">Omzetten naar item</flux:heading>

            <flux:radio.group wire:model="type" label="Type" variant="segmented">
                @foreach (\App\Enums\ItemType::cases() as $case)
                    <flux:radio :value="$case->value" :label="$case->label()" :icon="$case->icon()" />
                @endforeach
            </flux:radio.group>

            <flux:input wire:model="title" label="Titel" required />
            <flux:textarea wire:model="description" label="Beschrijving" rows="8" class:input="font-mono text-sm" />

            <flux:select wire:model="priority" label="Prioriteit">
                @foreach (\App\Enums\ItemPriority::cases() as $case)
                    <flux:select.option :value="$case->value">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:error name="message" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Annuleren</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Item aanmaken</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
