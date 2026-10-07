<?php

namespace Database\Factories;

use App\Enums\ItemPriority;
use App\Enums\ItemSource;
use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Models\Item;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'type' => fake()->randomElement(ItemType::cases()),
            'status' => ItemStatus::Open,
            'priority' => fake()->randomElement(ItemPriority::cases()),
            'title' => rtrim(fake()->sentence(5), '.'),
            'description' => fake()->paragraph(),
            'source' => ItemSource::Web,
        ];
    }

    public function status(ItemStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'closed_at' => $status === ItemStatus::Closed ? now() : null,
        ]);
    }
}
