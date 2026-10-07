<?php

namespace App\Models;

use App\Enums\ItemPriority;
use App\Enums\ItemSource;
use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Observers\ItemObserver;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property int $number
 * @property ItemType $type
 * @property ItemStatus $status
 * @property ItemPriority $priority
 * @property string $title
 * @property string|null $description
 * @property int|null $reporter_id
 * @property int|null $assignee_id
 * @property ItemSource $source
 * @property Carbon|null $closed_at
 * @property-read string $key
 * @property-read Project $project
 * @property-read User|null $reporter
 * @property-read User|null $assignee
 */
#[ObservedBy(ItemObserver::class)]
class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'status',
        'priority',
        'title',
        'description',
        'reporter_id',
        'assignee_id',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'type' => ItemType::class,
            'status' => ItemStatus::class,
            'priority' => ItemPriority::class,
            'source' => ItemSource::class,
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function key(): Attribute
    {
        /** @var Attribute<string, never> */
        $attribute = Attribute::get(fn (): string => $this->project->key.'-'.$this->number);

        return $attribute;
    }

    /**
     * Resolves a key such as BONKBOX-42.
     *
     * @throws ModelNotFoundException<Item>
     */
    public static function findByKeyOrFail(string $key): self
    {
        if (! preg_match('/^([A-Za-z][A-Za-z0-9]*)-(\d+)$/', $key, $matches)) {
            throw (new ModelNotFoundException)->setModel(self::class, [$key]);
        }

        return static::query()
            ->whereHas('project', fn (Builder $query) => $query->where('key', strtoupper($matches[1])))
            ->where('number', (int) $matches[2])
            ->first() ?? throw (new ModelNotFoundException)->setModel(self::class, [$key]);
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $field === null ? static::findByKeyOrFail((string) $value) : parent::resolveRouteBinding($value, $field);
    }

    public function getRouteKey(): string
    {
        return $this->key;
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * @return BelongsToMany<Label, $this>
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class);
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * The voice-agent message this item was made from, if any.
     *
     * @return HasOne<InboxMessage, $this>
     */
    public function inboxMessage(): HasOne
    {
        return $this->hasOne(InboxMessage::class);
    }

    /**
     * @return HasMany<Activity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }
}
