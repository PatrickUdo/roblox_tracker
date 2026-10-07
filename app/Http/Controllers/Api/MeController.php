<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * Current user and token
     *
     * Shows who the token belongs to, its abilities and (if any) the project it is limited to.
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $token = $user->currentAccessToken();

        return response()->json([
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            'token' => $token instanceof PersonalAccessToken ? [
                'name' => $token->name,
                'abilities' => $token->abilities,
                'project' => $token->project?->slug,
                'last_used_at' => $token->last_used_at,
            ] : null,
        ]);
    }
}
