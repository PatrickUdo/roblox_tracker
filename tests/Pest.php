<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Attach a real Sanctum token to $user, as auth:sanctum does for API and MCP requests.
 *
 * @param  list<string>  $abilities
 */
function withToken(User $user, array $abilities = ['*'], ?Project $project = null): User
{
    return $user->withAccessToken($user->createScopedToken('test', $abilities, $project)->accessToken);
}
