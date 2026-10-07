<?php

namespace Database\Factories;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'key' => strtoupper(Str::random(1)).fake()->unique()->numerify('###'),
            'description' => fake()->sentence(),
        ];
    }

    public function withOwner(User $user): static
    {
        return $this->hasAttached($user, ['role' => ProjectRole::Owner->value], 'members');
    }

    public function withMember(User $user): static
    {
        return $this->hasAttached($user, ['role' => ProjectRole::Member->value], 'members');
    }
}
