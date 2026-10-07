<?php

namespace App\Jobs;

use App\Models\Webhook;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class DeliverWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300, 900];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public Webhook $webhook,
        public array $payload,
    ) {}

    public function handle(): void
    {
        $body = (string) json_encode($this->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $response = Http::timeout(10)
            ->withHeaders([
                'X-Tracker-Event' => $this->payload['event'],
                'X-Tracker-Signature' => 'sha256='.hash_hmac('sha256', $body, $this->webhook->secret),
            ])
            ->withBody($body, 'application/json')
            ->post($this->webhook->url);

        $this->webhook->forceFill([
            'last_delivered_at' => now(),
            'last_status' => $response->status(),
        ])->save();

        $response->throw();
    }
}
