<div>
    <x-project-header :project="$project" current="settings" />

    <div class="max-w-3xl space-y-12">
        <section>
            <flux:heading size="lg">Algemeen</flux:heading>
            <form wire:submit="saveGeneral" class="mt-4 space-y-5">
                <flux:input wire:model="name" label="Naam" required />
                <flux:textarea wire:model="description" label="Beschrijving" rows="3" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input :value="$project->key" label="Key" description="Staat vast, zodat bestaande verwijzingen blijven kloppen." readonly class:input="font-mono" />
                    <flux:input :value="$project->slug" label="Slug" description="Gebruikt in URL's en de API." readonly class:input="font-mono" />
                </div>
                <flux:button type="submit" variant="primary">Opslaan</flux:button>
            </form>
        </section>

        <section>
            <flux:heading size="lg">Leden</flux:heading>
            <flux:text class="mt-1">Eigenaren beheren leden, labels en webhooks; leden maken en bewerken items.</flux:text>

            <div class="mt-4 divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                @foreach ($members as $member)
                    <div wire:key="member-{{ $member->id }}" class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                        <div class="flex items-center gap-3">
                            <flux:avatar size="sm" :initials="$member->initials()" />
                            <div>
                                <p class="text-sm font-medium">{{ $member->name }} @if ($member->is(auth()->user()))<span class="text-zinc-500">(jij)</span>@endif</p>
                                <p class="text-sm text-zinc-500">{{ $member->email }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:select size="sm" class="w-36" wire:change="changeRole({{ $member->id }}, $event.target.value)">
                                @foreach (\App\Enums\ProjectRole::cases() as $role)
                                    <flux:select.option :value="$role->value" :selected="$member->pivot->role === $role->value">{{ $role->label() }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="removeMember({{ $member->id }})" wire:confirm="{{ $member->name }} uit het project verwijderen?" aria-label="Verwijderen" />
                        </div>
                    </div>
                @endforeach
            </div>

            <form wire:submit="addMember" class="mt-4 flex flex-wrap items-start gap-2">
                <div class="min-w-64 flex-1">
                    <flux:input wire:model="memberEmail" type="email" placeholder="E-mailadres" aria-label="E-mailadres" />
                    <flux:error name="memberEmail" />
                </div>
                <flux:select wire:model="memberRole" class="w-36" aria-label="Rol">
                    @foreach (\App\Enums\ProjectRole::cases() as $role)
                        <flux:select.option :value="$role->value">{{ $role->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:button type="submit" icon="user-plus">Toevoegen</flux:button>
            </form>
        </section>

        <section>
            <flux:heading size="lg">Labels</flux:heading>

            @if ($labels->isNotEmpty())
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($labels as $label)
                        <span wire:key="label-{{ $label->id }}" class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 py-1 pl-2.5 pr-1 text-sm dark:border-zinc-700">
                            <span class="size-2.5 rounded-full" style="background-color: {{ $label->color }}"></span>
                            {{ $label->name }}
                            <span class="text-zinc-400">{{ $label->items_count }}</span>
                            <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="deleteLabel({{ $label->id }})" wire:confirm="Label {{ $label->name }} verwijderen? Het verdwijnt van alle items." aria-label="Verwijderen" />
                        </span>
                    @endforeach
                </div>
            @endif

            <form wire:submit="addLabel" class="mt-4 flex flex-wrap items-start gap-2">
                <div class="min-w-48 flex-1">
                    <flux:input wire:model="labelName" placeholder="Naam" aria-label="Labelnaam" />
                    <flux:error name="labelName" />
                </div>
                <input type="color" wire:model="labelColor" class="h-10 w-14 cursor-pointer rounded-lg border border-zinc-200 bg-white p-1 dark:border-zinc-600 dark:bg-zinc-800" aria-label="Kleur" />
                <flux:button type="submit" icon="tag">Toevoegen</flux:button>
            </form>
        </section>

        <section>
            <flux:heading size="lg">Webhooks</flux:heading>
            <flux:text class="mt-1">
                Bij elk nieuw item, elke wijziging, statusovergang en reactie volgt een POST met JSON. De header
                <code class="font-mono text-xs">X-Tracker-Signature</code> bevat <code class="font-mono text-xs">sha256=</code> plus een HMAC van de body met het secret.
            </flux:text>

            @if ($newWebhookSecret)
                <flux:callout icon="key" color="amber" class="mt-4">
                    <flux:callout.heading>Bewaar dit secret nu</flux:callout.heading>
                    <flux:callout.text>
                        Het wordt maar één keer getoond:
                        <code class="mt-1 block break-all font-mono text-sm" x-data x-on:click="navigator.clipboard.writeText($el.textContent.trim()); $flux.toast('Gekopieerd')">{{ $newWebhookSecret }}</code>
                    </flux:callout.text>
                </flux:callout>
            @endif

            @if ($webhooks->isNotEmpty())
                <div class="mt-4 divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                    @foreach ($webhooks as $webhook)
                        <div wire:key="webhook-{{ $webhook->id }}" class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                            <div class="min-w-0">
                                <p class="truncate font-mono">{{ $webhook->url }}</p>
                                <p class="text-zinc-500">
                                    @if ($webhook->last_delivered_at)
                                        Laatst verstuurd {{ $webhook->last_delivered_at->diffForHumans() }} · HTTP {{ $webhook->last_status }}
                                    @else
                                        Nog niets verstuurd
                                    @endif
                                </p>
                            </div>
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteWebhook({{ $webhook->id }})" wire:confirm="Deze webhook verwijderen?" aria-label="Verwijderen" />
                        </div>
                    @endforeach
                </div>
            @endif

            <form wire:submit="addWebhook" class="mt-4 flex flex-wrap items-start gap-2">
                <div class="min-w-64 flex-1">
                    <flux:input wire:model="webhookUrl" type="url" placeholder="https://…" aria-label="Webhook-URL" />
                    <flux:error name="webhookUrl" />
                </div>
                <flux:button type="submit" icon="plus">Toevoegen</flux:button>
            </form>
        </section>

        <section class="rounded-xl border border-red-200 p-5 dark:border-red-900/60">
            <flux:heading size="lg" class="text-red-700 dark:text-red-400">Project verwijderen</flux:heading>
            <flux:text class="mt-1">Verwijdert het project met alle items, reacties, labels en inboxberichten. Dit kan niet ongedaan worden gemaakt.</flux:text>
            <form wire:submit="deleteProject" class="mt-4 flex flex-wrap items-start gap-2">
                <div class="min-w-64 flex-1">
                    <flux:input wire:model="confirmKey" placeholder="Typ {{ $project->key }} om te bevestigen" class:input="font-mono" aria-label="Bevestiging" />
                    <flux:error name="confirmKey" />
                </div>
                <flux:button type="submit" variant="danger">Verwijderen</flux:button>
            </form>
        </section>
    </div>
</div>
