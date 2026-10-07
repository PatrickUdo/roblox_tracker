<?php

use App\Actions\Items\CreateItem;
use App\Actions\Items\TransitionItem;
use App\Actions\Webhooks\CreateWebhook;
use App\Enums\ItemSource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Http::fake(['hooks.example.com/*' => Http::response('ok')]);
    $this->owner = User::factory()->create();
    $this->project = Project::factory()->withOwner($this->owner)->create(['key' => 'BONKBOX']);
    $this->webhook = app(CreateWebhook::class)->handle($this->owner, $this->project, ['url' => 'https://hooks.example.com/tracker']);
});

it('posts signed events for item changes', function () {
    $item = app(CreateItem::class)->handle($this->owner, $this->project, ['type' => 'bug', 'title' => 'Kapot'], ItemSource::Web);
    app(TransitionItem::class)->handle($this->owner, $item, 'in_progress');

    Http::assertSentCount(2);
    Http::assertSent(function (Request $request) {
        $signature = 'sha256='.hash_hmac('sha256', $request->body(), $this->webhook->secret);

        return $request->url() === 'https://hooks.example.com/tracker'
            && $request->header('X-Tracker-Event')[0] === 'item.transitioned'
            && $request->header('X-Tracker-Signature')[0] === $signature
            && $request['item']['key'] === 'BONKBOX-1'
            && $request['new'] === ['status' => 'in_progress'];
    });

    expect($this->webhook->fresh()->last_status)->toBe(200);
});

it('only accepts http(s) URLs', function () {
    app(CreateWebhook::class)->handle($this->owner, $this->project, ['url' => 'ftp://example.com']);
})->throws(ValidationException::class);
