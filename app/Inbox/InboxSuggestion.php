<?php

namespace App\Inbox;

use App\Enums\ItemPriority;
use App\Enums\ItemType;

final readonly class InboxSuggestion
{
    public function __construct(
        public ItemType $type,
        public string $title,
        public string $description,
        public ItemPriority $priority,
        public float $confidence,
    ) {}

    /**
     * @return array{type: string, title: string, description: string, priority: string, confidence: float}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority->value,
            'confidence' => $this->confidence,
        ];
    }
}
