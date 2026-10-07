<?php

namespace App\Mcp;

use App\Enums\ItemStatus;
use App\Models\Activity;
use App\Models\Item;
use App\Models\Project;

/**
 * Plain-text renderings for MCP responses: compact for lists, complete for single items.
 */
class Markdown
{
    public static function itemLine(Item $item): string
    {
        $line = "{$item->key} · {$item->type->value} · {$item->status->value} · {$item->priority->value} · {$item->title}";

        if ($item->assignee) {
            $line .= " (assignee: {$item->assignee->email})";
        }

        if ($item->labels->isNotEmpty()) {
            $line .= ' ['.$item->labels->pluck('name')->sort()->implode(', ').']';
        }

        return $line;
    }

    public static function item(Item $item): string
    {
        $item->loadMissing(['project', 'reporter', 'assignee', 'labels', 'comments.user', 'activities.user']);

        $allowed = implode(', ', array_map(fn (ItemStatus $s) => $s->value, $item->status->allowedTransitions()));

        $lines = [
            "# {$item->key}: {$item->title}",
            '',
            "- Project: {$item->project->name} ({$item->project->slug})",
            "- Type: {$item->type->value}",
            "- Status: {$item->status->value} (allowed next: {$allowed})",
            "- Priority: {$item->priority->value}",
            '- Assignee: '.($item->assignee ? "{$item->assignee->name} <{$item->assignee->email}>" : 'none'),
            '- Reporter: '.($item->reporter->name ?? 'unknown'),
            '- Labels: '.($item->labels->isNotEmpty() ? $item->labels->pluck('name')->sort()->implode(', ') : 'none'),
            "- Source: {$item->source->value}",
            "- Created: {$item->created_at?->toIso8601String()}",
            "- Updated: {$item->updated_at?->toIso8601String()}",
            '- URL: '.route('items.show', $item),
            '',
            '## Description',
            '',
            filled($item->description) ? (string) $item->description : '_No description._',
        ];

        $lines[] = '';
        $lines[] = '## Comments';
        $lines[] = '';

        if ($item->comments->isEmpty()) {
            $lines[] = '_No comments._';
        }

        foreach ($item->comments->sortBy('id') as $comment) {
            $lines[] = "**{$comment->user?->name}** ({$comment->created_at?->toIso8601String()}):";
            $lines[] = $comment->body;
            $lines[] = '';
        }

        $lines[] = '';
        $lines[] = '## Activity';
        $lines[] = '';

        foreach ($item->activities->sortBy('id') as $activity) {
            $lines[] = '- '.self::activity($activity);
        }

        return implode("\n", $lines);
    }

    public static function project(Project $project, int $openItemLimit = 50): string
    {
        $open = $project->items()
            ->with(['project', 'assignee', 'labels'])
            ->whereIn('status', [ItemStatus::Open, ItemStatus::InProgress])
            ->latest('number')
            ->limit($openItemLimit)
            ->get();

        $lines = [
            "# {$project->name} ({$project->key})",
            '',
            "Slug: {$project->slug}. Item keys look like {$project->key}-42.",
            '',
            filled($project->description) ? (string) $project->description : '_No description._',
            '',
            'Labels: '.($project->labels->isNotEmpty() ? $project->labels->pluck('name')->sort()->implode(', ') : 'none'),
            'Members: '.$project->members->map(fn ($m) => "{$m->name} <{$m->email}>")->implode(', '),
            '',
            "## Open and in-progress items ({$open->count()})",
            '',
        ];

        foreach ($open as $item) {
            $lines[] = '- '.self::itemLine($item);
        }

        return implode("\n", $lines);
    }

    private static function activity(Activity $activity): string
    {
        $who = $activity->user->name ?? 'system';
        $at = $activity->created_at?->toIso8601String();

        $what = match ($activity->event) {
            'transitioned' => 'changed status from '.($activity->old['status'] ?? '?').' to '.($activity->new['status'] ?? '?'),
            'updated' => 'changed '.implode(', ', array_keys($activity->new ?? [])),
            'commented' => 'commented',
            'created' => 'created the item',
            default => $activity->event,
        };

        return "{$at} {$who} {$what}";
    }
}
