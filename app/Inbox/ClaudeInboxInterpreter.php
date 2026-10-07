<?php

namespace App\Inbox;

use Anthropic\Client;
use App\Enums\ItemPriority;
use App\Enums\ItemType;

class ClaudeInboxInterpreter implements InboxInterpreter
{
    public function __construct(
        private Client $client,
        private string $model,
        private string $effort,
        private string $prompt,
    ) {}

    public function interpret(string $text): InboxSuggestion
    {
        $message = $this->client->beta->messages->create(
            model: $this->model,
            maxTokens: 4096,
            system: $this->prompt,
            messages: [
                ['role' => 'user', 'content' => "<melding>\n{$text}\n</melding>"],
            ],
            outputConfig: [
                'effort' => $this->effort,
                'format' => [
                    'type' => 'json_schema',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => ['type' => 'string', 'enum' => array_column(ItemType::cases(), 'value')],
                            'title' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'priority' => ['type' => 'string', 'enum' => array_column(ItemPriority::cases(), 'value')],
                            'confidence' => ['type' => 'number'],
                        ],
                        'required' => ['type', 'title', 'description', 'priority', 'confidence'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            // If the model declines on policy grounds, let the API retry on its default fallback model.
            fallbacks: 'default',
            betas: ['server-side-fallback-2026-07-01'],
        );

        if ($message->stopReason === 'refusal') {
            throw new InterpretationFailed('The model declined to interpret this message.');
        }

        if ($message->stopReason === 'max_tokens') {
            throw new InterpretationFailed('The model response was cut off.');
        }

        $json = null;

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $json = json_decode($block->text, true);
                break;
            }
        }

        if (! is_array($json)) {
            throw new InterpretationFailed('The model did not return JSON.');
        }

        return self::suggestionFrom($json);
    }

    /**
     * @param  array<mixed>  $json
     */
    public static function suggestionFrom(array $json): InboxSuggestion
    {
        $type = ItemType::tryFrom((string) ($json['type'] ?? ''));
        $priority = ItemPriority::tryFrom((string) ($json['priority'] ?? ''));
        $title = trim((string) ($json['title'] ?? ''));

        if ($type === null || $priority === null || $title === '') {
            throw new InterpretationFailed('The model returned an incomplete suggestion.');
        }

        return new InboxSuggestion(
            type: $type,
            title: mb_substr($title, 0, 255),
            description: trim((string) ($json['description'] ?? '')),
            priority: $priority,
            confidence: max(0.0, min(1.0, (float) ($json['confidence'] ?? 0))),
        );
    }
}
