<?php

namespace App\Actions\Tokens;

use App\Models\User;

class RevokeApiToken
{
    public function handle(User $actor, int $tokenId): void
    {
        $actor->tokens()->whereKey($tokenId)->delete();
    }
}
