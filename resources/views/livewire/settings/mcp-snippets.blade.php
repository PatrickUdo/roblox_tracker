{{-- MCP config snippets. Expects $claudeCodeCommand, $mcpJson and $desktopJson. --}}
<div x-data="{ tab: 'code' }" class="mt-4">
    <div class="flex gap-1 text-sm">
        <button type="button" x-on:click="tab = 'code'" :class="tab === 'code' ? 'bg-zinc-200 dark:bg-zinc-700' : 'text-zinc-500'" class="rounded-md px-2.5 py-1">Claude Code</button>
        <button type="button" x-on:click="tab = 'json'" :class="tab === 'json' ? 'bg-zinc-200 dark:bg-zinc-700' : 'text-zinc-500'" class="rounded-md px-2.5 py-1">.mcp.json</button>
        <button type="button" x-on:click="tab = 'desktop'" :class="tab === 'desktop' ? 'bg-zinc-200 dark:bg-zinc-700' : 'text-zinc-500'" class="rounded-md px-2.5 py-1">Claude Desktop</button>
    </div>

    @foreach (['code' => $claudeCodeCommand, 'json' => $mcpJson, 'desktop' => $desktopJson] as $tab => $snippet)
        <div x-show="tab === '{{ $tab }}'" @if ($tab !== 'code') x-cloak @endif class="relative mt-2">
            <pre class="overflow-x-auto rounded-lg bg-zinc-900 p-4 pr-12 text-xs leading-relaxed text-zinc-100"><code>{{ $snippet }}</code></pre>
            <flux:button
                size="xs" variant="subtle" icon="clipboard" aria-label="Kopiëren"
                class="!absolute right-2 top-2 !text-zinc-300"
                x-on:click="navigator.clipboard.writeText(@js($snippet)); $flux.toast('Gekopieerd')"
            />
        </div>
    @endforeach

    <flux:text size="sm" class="mt-2" x-show="tab === 'desktop'" x-cloak>
        Plak dit in <code class="font-mono">claude_desktop_config.json</code>; <code class="font-mono">mcp-remote</code> verbindt Claude Desktop met een HTTP-server met een header.
    </flux:text>
</div>
