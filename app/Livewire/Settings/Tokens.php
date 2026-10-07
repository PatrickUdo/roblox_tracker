<?php

namespace App\Livewire\Settings;

use App\Actions\Tokens\CreateApiToken;
use App\Actions\Tokens\RevokeApiToken;
use App\Enums\TokenAbility;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('API-tokens')]
class Tokens extends Component
{
    public const PRESETS = [
        'full' => ['projects:read', 'projects:write', 'items:read', 'items:write', 'comments:write', 'inbox:write'],
        'agent' => ['projects:read', 'items:read', 'items:write', 'comments:write'],
        'read' => ['projects:read', 'items:read'],
        'voice' => ['inbox:write'],
    ];

    public string $name = '';

    /** @var list<string> */
    public array $abilities = self::PRESETS['agent'];

    public string $project_id = '';

    /** Shown once, right after the token is created. */
    public ?string $plainTextToken = null;

    public function preset(string $preset): void
    {
        $this->abilities = self::PRESETS[$preset] ?? $this->abilities;
    }

    public function create(CreateApiToken $create): void
    {
        $token = $create->handle($this->user(), [
            'name' => $this->name,
            'abilities' => $this->abilities,
            'project_id' => $this->project_id !== '' ? (int) $this->project_id : null,
        ]);

        $this->plainTextToken = $token->plainTextToken;
        $this->reset(['name', 'project_id']);
        $this->abilities = self::PRESETS['agent'];

        Flux::modal('new-token')->show();
    }

    public function revoke(int $tokenId, RevokeApiToken $revoke): void
    {
        $revoke->handle($this->user(), $tokenId);
        Flux::toast(text: 'Token ingetrokken.');
    }

    public function forgetToken(): void
    {
        $this->plainTextToken = null;
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    public function render(): View
    {
        $mcpUrl = url('/mcp');
        $token = $this->plainTextToken ?? '<token>';

        return view('livewire.settings.tokens', [
            'tokens' => PersonalAccessToken::whereMorphedTo('tokenable', $this->user())->with('project')->latest('id')->get(),
            'projects' => $this->user()->projects()->orderBy('name')->get(),
            'allAbilities' => TokenAbility::cases(),
            'mcpUrl' => $mcpUrl,
            'claudeCodeCommand' => "claude mcp add --transport http tracker {$mcpUrl} --header \"Authorization: Bearer {$token}\"",
            'mcpJson' => json_encode(['mcpServers' => ['tracker' => [
                'type' => 'http',
                'url' => $mcpUrl,
                'headers' => ['Authorization' => "Bearer {$token}"],
            ]]], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'desktopJson' => json_encode(['mcpServers' => ['tracker' => [
                'command' => 'npx',
                'args' => ['-y', 'mcp-remote', $mcpUrl, '--header', "Authorization: Bearer {$token}"],
            ]]], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
