<?php

namespace App\Models;

use App\Enums\InboxStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Free text from the voice agent, kept so the transcript stays traceable to its item.
 *
 * @property int $id
 * @property int $project_id
 * @property int|null $user_id
 * @property string|null $reporter
 * @property string $raw_text
 * @property InboxStatus $status
 * @property array{type?: string, title?: string, description?: string, priority?: string, confidence?: float}|null $suggestion
 * @property int|null $item_id
 * @property string|null $error
 * @property-read Project $project
 * @property-read User|null $user
 * @property-read Item|null $item
 */
class InboxMessage extends Model
{
    protected $fillable = [
        'user_id',
        'reporter',
        'raw_text',
        'status',
        'suggestion',
        'item_id',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'status' => InboxStatus::class,
            'suggestion' => 'array',
        ];
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
