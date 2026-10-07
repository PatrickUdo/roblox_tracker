{{-- One activity log line. Expects $activity. --}}
@php
    $who = $activity->user?->name ?? 'Systeem';
    $statusLabel = fn (?string $value) => \App\Enums\ItemStatus::tryFrom((string) $value)?->label() ?? $value;
    $fieldLabels = ['title' => 'titel', 'description' => 'beschrijving', 'priority' => 'prioriteit', 'type' => 'type', 'assignee_id' => 'toewijzing', 'labels' => 'labels'];
@endphp

<span class="font-medium text-zinc-700 dark:text-zinc-200">{{ $who }}</span>
@switch($activity->event)
    @case('created')
        heeft dit item aangemaakt
        @if (($activity->new['source'] ?? null) && $activity->new['source'] !== 'web')
            via {{ \App\Enums\ItemSource::tryFrom($activity->new['source'])?->label() }}
        @endif
        @break
    @case('transitioned')
        zette de status van <strong>{{ $statusLabel($activity->old['status'] ?? null) }}</strong> naar <strong>{{ $statusLabel($activity->new['status'] ?? null) }}</strong>
        @break
    @case('commented')
        heeft gereageerd
        @break
    @case('updated')
        wijzigde {{ collect(array_keys($activity->new ?? []))->map(fn ($field) => $fieldLabels[$field] ?? $field)->join(', ', ' en ') }}
        @break
    @default
        {{ $activity->event }}
@endswitch
