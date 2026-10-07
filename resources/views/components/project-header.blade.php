{{-- `current` is passed in because request()->routeIs() is wrong during Livewire updates. --}}
@props(['project', 'current'])

@php
    $openInbox = $project->inboxMessages()->whereIn('status', ['pending', 'draft', 'failed'])->count();
@endphp

<div class="mb-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <flux:heading size="xl" level="1">{{ $project->name }}</flux:heading>
                <flux:badge size="sm">{{ $project->key }}</flux:badge>
            </div>
            @if ($project->description)
                <flux:text class="mt-1">{{ $project->description }}</flux:text>
            @endif
        </div>

        <div class="flex items-center gap-2">
            {{ $actions ?? '' }}
        </div>
    </div>

    <flux:navbar class="-mb-px mt-4 border-b border-zinc-200 dark:border-zinc-700">
        <flux:navbar.item icon="view-columns" :href="route('projects.board', $project)" :current="$current === 'board'" wire:navigate>Bord</flux:navbar.item>
        <flux:navbar.item icon="list-bullet" :href="route('projects.list', $project)" :current="$current === 'list'" wire:navigate>Lijst</flux:navbar.item>
        <flux:navbar.item icon="inbox" :href="route('projects.inbox', $project)" :current="$current === 'inbox'" :badge="$openInbox ?: null" wire:navigate>Inbox</flux:navbar.item>
        @can('manage', $project)
            <flux:navbar.item icon="cog-6-tooth" :href="route('projects.settings', $project)" :current="$current === 'settings'" wire:navigate>Instellingen</flux:navbar.item>
        @endcan
    </flux:navbar>
</div>
