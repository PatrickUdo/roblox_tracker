<?php

namespace Database\Seeders;

use App\Actions\Items\CreateItem;
use App\Actions\Labels\CreateLabel;
use App\Actions\Projects\CreateProject;
use App\Enums\ItemSource;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $project = app(CreateProject::class)->handle($user, [
            'name' => 'Bonkbox Studios',
            'key' => 'BONKBOX',
            'description' => 'Bugs en features voor Bonkbox Studios.',
        ]);

        foreach (['ui' => '#3b82f6', 'backend' => '#10b981', 'voice' => '#f59e0b'] as $name => $color) {
            app(CreateLabel::class)->handle($user, $project, ['name' => $name, 'color' => $color]);
        }

        $createItem = app(CreateItem::class);

        $createItem->handle($user, $project, [
            'type' => 'bug',
            'title' => 'Login-knop reageert niet op mobiel',
            'priority' => 'high',
            'labels' => ['ui'],
        ], ItemSource::Web);

        $createItem->handle($user, $project, [
            'type' => 'feature',
            'title' => 'Kanban filteren op label',
            'labels' => ['ui'],
        ], ItemSource::Web);
    }
}
