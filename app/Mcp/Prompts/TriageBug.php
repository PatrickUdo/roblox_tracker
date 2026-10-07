<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Name('triage_bug')]
#[Description('Turns a loose bug report into a structured item (title, steps, expected and actual behaviour, priority) and files it with create_item.')]
class TriageBug extends Prompt
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'report' => ['required', 'string'],
            'project' => ['nullable', 'string'],
        ]);

        $project = filled($validated['project'] ?? null)
            ? "in project \"{$validated['project']}\""
            : 'in the right project (call list_projects first if unsure)';

        return Response::text(<<<PROMPT
            Triage the bug report below and file it {$project}.

            1. Call search_items with a few keywords to check whether this bug is already known. If it is, add a
               comment with the new information to the existing item instead of creating a duplicate, and stop.
            2. Otherwise write:
               - a short, specific title;
               - a markdown description with the sections "Steps to reproduce", "Expected behaviour" and
                 "Actual behaviour" (leave out what the report does not say; do not invent details), plus any
                 environment details mentioned;
               - a priority: critical (work is blocked or there is a safety or data-loss risk), high (seriously
                 hinders work), medium (normal) or low (minor annoyance).
            3. Call create_item with type "bug" and report the returned key.

            <report>
            {$validated['report']}
            </report>
            PROMPT);
    }

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument('report', 'The bug report as written or spoken.', required: true),
            new Argument('project', 'Project slug or key.'),
        ];
    }
}
