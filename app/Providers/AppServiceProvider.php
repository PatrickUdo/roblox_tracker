<?php

namespace App\Providers;

use Anthropic\Client;
use App\Inbox\ClaudeInboxInterpreter;
use App\Inbox\InboxInterpreter;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(InboxInterpreter::class, fn () => new ClaudeInboxInterpreter(
            new Client(apiKey: (string) config('inbox.anthropic.api_key')),
            (string) config('inbox.anthropic.model'),
            (string) config('inbox.anthropic.effort'),
            (string) config('inbox.prompt'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by(
            $request->user()?->currentAccessToken()?->getKey() ?? $request->ip(),
        ));

        // The spec holds no secrets, and clients such as the voice agent need it.
        Gate::define('viewApiDocs', fn (?User $user = null) => true);

        Scramble::routes(fn (Route $route) => Str::startsWith($route->uri, 'api/v1'));
        Scramble::extendOpenApi(fn (OpenApi $openApi) => $openApi->secure(SecurityScheme::http('bearer')));
    }
}
