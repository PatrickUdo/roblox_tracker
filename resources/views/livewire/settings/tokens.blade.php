<div>
    <div class="mb-6">
        <flux:heading size="xl" level="1">API-tokens</flux:heading>
        <flux:text class="mt-1">Voor de REST API, de MCP-server en de voice-agent. Een token handelt namens jou, binnen de rechten die je aanvinkt.</flux:text>
    </div>

    <div class="grid gap-10 lg:grid-cols-[minmax(0,26rem)_1fr]">
        <form wire:submit="create" class="space-y-5">
            <flux:heading size="lg">Nieuw token</flux:heading>

            <flux:input wire:model="name" label="Naam" placeholder="Bijvoorbeeld: Claude Code laptop" required />

            <div>
                <flux:label>Rechten</flux:label>
                <div class="mt-2 flex flex-wrap gap-1">
                    <flux:button size="xs" wire:click="preset('agent')">AI-agent</flux:button>
                    <flux:button size="xs" wire:click="preset('read')">Alleen lezen</flux:button>
                    <flux:button size="xs" wire:click="preset('voice')">Voice-agent</flux:button>
                    <flux:button size="xs" wire:click="preset('full')">Alles</flux:button>
                </div>
                <flux:checkbox.group wire:model="abilities" class="mt-3 space-y-2">
                    @foreach ($allAbilities as $ability)
                        <flux:checkbox :value="$ability->value" :label="$ability->label()" :description="$ability->value" />
                    @endforeach
                </flux:checkbox.group>
                <flux:error name="abilities" />
            </div>

            <flux:select wire:model="project_id" label="Project" description="Beperk het token tot één project. Zonder keuze werkt het voor al je projecten.">
                <flux:select.option value="">Alle projecten</flux:select.option>
                @foreach ($projects as $project)
                    <flux:select.option :value="$project->id">{{ $project->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:button type="submit" variant="primary" icon="key">Token aanmaken</flux:button>
        </form>

        <div>
            <flux:heading size="lg">Je tokens</flux:heading>

            @if ($tokens->isEmpty())
                <flux:text class="mt-3">Je hebt nog geen tokens.</flux:text>
            @else
                <div class="mt-4 divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                    @foreach ($tokens as $token)
                        <div wire:key="token-{{ $token->id }}" class="flex flex-wrap items-start justify-between gap-3 px-4 py-3">
                            <div class="min-w-0 space-y-1.5">
                                <p class="font-medium">{{ $token->name }}</p>
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($token->abilities as $ability)
                                        <flux:badge size="sm" class="font-mono">{{ $ability }}</flux:badge>
                                    @endforeach
                                </div>
                                <p class="text-sm text-zinc-500">
                                    {{ $token->project ? 'Alleen '.$token->project->name : 'Alle projecten' }}
                                    · aangemaakt {{ $token->created_at->diffForHumans() }}
                                    · {{ $token->last_used_at ? 'laatst gebruikt '.$token->last_used_at->diffForHumans() : 'nooit gebruikt' }}
                                </p>
                            </div>
                            <flux:button size="sm" variant="ghost" wire:click="revoke({{ $token->id }})" wire:confirm="Token {{ $token->name }} intrekken? Clients die het gebruiken verliezen direct toegang.">Intrekken</flux:button>
                        </div>
                    @endforeach
                </div>
            @endif

            <flux:heading size="lg" class="mt-10">MCP-server koppelen</flux:heading>
            <flux:text class="mt-1">
                De MCP-server draait op <code class="font-mono text-sm">{{ $mcpUrl }}</code> en gebruikt hetzelfde token als de API.
                Na het aanmaken van een token staan de snippets hieronder klaar met het token erin.
            </flux:text>
            @include('livewire.settings.mcp-snippets')
        </div>
    </div>

    <flux:modal name="new-token" class="w-full max-w-2xl" x-on:close="$wire.forgetToken()">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Je nieuwe token</flux:heading>
                <flux:text class="mt-1">Kopieer het nu: het wordt maar één keer getoond.</flux:text>
            </div>

            <flux:input :value="$plainTextToken" readonly copyable class:input="font-mono text-sm" />

            @include('livewire.settings.mcp-snippets')

            <div class="flex justify-end">
                <flux:modal.close>
                    <flux:button variant="primary">Klaar</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
