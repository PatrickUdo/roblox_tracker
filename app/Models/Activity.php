<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $item_id
 * @property int|null $user_id
 * @property string $event
 * @property array<string, mixed>|null $old
 * @property array<string, mixed>|null $new
 * @property-read User|null $user
 */
class Activity extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'event',
        'old',
        'new',
    ];

    protected function casts(): array
    {
        return [
            'old' => 'array',
            'new' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
