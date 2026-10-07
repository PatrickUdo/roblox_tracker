<?php

namespace App\Models;

use App\Enums\ProjectRole;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $key
 * @property string|null $description
 * @property int $next_number
 */
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'key',
        'description',
    ];

    /**
     * Projects the user is a member of, narrowed to the token's project when it is scoped.
     *
     * @param  Builder<Project>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        $query->whereHas('members', fn (Builder $members) => $members->whereKey($user->id));

        if (($projectId = PersonalAccessToken::projectIdOf($user->currentAccessToken())) !== null) {
            $query->whereKey($projectId);
        }
    }

    /**
     * Agents and the voice agent may name a project by slug or by key.
     *
     * @throws ModelNotFoundException<Project>
     */
    public static function findBySlugOrKeyOrFail(string $value): self
    {
        return static::query()
            ->where('slug', $value)
            ->orWhere('key', strtoupper($value))
            ->firstOrFail();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<Item, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * @return HasMany<Label, $this>
     */
    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }

    /**
     * @return HasMany<InboxMessage, $this>
     */
    public function inboxMessages(): HasMany
    {
        return $this->hasMany(InboxMessage::class);
    }

    /**
     * @return HasMany<Webhook, $this>
     */
    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function roleOf(User $user): ?ProjectRole
    {
        $role = $this->members()->whereKey($user->id)->value('role');

        return $role === null ? null : ProjectRole::from($role);
    }

    public function hasMember(User $user): bool
    {
        return $this->roleOf($user) !== null;
    }

    public function isOwner(User $user): bool
    {
        return $this->roleOf($user) === ProjectRole::Owner;
    }

    /**
     * Hands out the next item number. The row lock serialises concurrent
     * callers; inside an outer transaction it is held until that commits.
     */
    public function allocateNumber(): int
    {
        return DB::transaction(function () {
            $locked = static::query()->whereKey($this->id)->lockForUpdate()->firstOrFail();
            $number = $locked->next_number;
            $locked->increment('next_number');

            $this->next_number = $number + 1;

            return $number;
        });
    }
}
