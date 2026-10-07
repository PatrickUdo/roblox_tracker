<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * A token without project_id covers every project its owner belongs to;
 * with project_id it is limited to that single project.
 *
 * @property int|null $project_id
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'project_id',
        'expires_at',
    ];

    /**
     * The project a token is limited to, or null when it covers all of the owner's projects.
     * Reads the raw attribute so test doubles such as Sanctum::actingAs() count as unscoped.
     */
    public static function projectIdOf(mixed $token): ?int
    {
        $projectId = $token instanceof self ? $token->getAttribute('project_id') : null;

        return is_numeric($projectId) ? (int) $projectId : null;
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
