<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_the_dashboard_sends_authenticated_users_to_their_projects(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get('/dashboard')->assertRedirect('/projects');
        $this->get('/projects')->assertOk();
    }
}
