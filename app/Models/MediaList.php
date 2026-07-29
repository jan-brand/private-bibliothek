<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $library_id
 * @property int $owner_user_id
 * @property string $name
 * @property string|null $description
 * @property string $visibility
 */
class MediaList extends Model
{
    public const VISIBILITY_PRIVATE = 'private';

    public const VISIBILITY_SHARED = 'shared';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'library_id',
        'owner_user_id',
        'name',
        'description',
        'visibility',
    ];

    protected function casts(): array
    {
        return [
            'library_id' => 'integer',
            'owner_user_id' => 'integer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function visibilities(): array
    {
        return [
            self::VISIBILITY_PRIVATE => 'Privat',
            self::VISIBILITY_SHARED => 'Gemeinsam',
        ];
    }

    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MediaListItem::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    /**
     * @param  Builder<MediaList>  $query
     * @return Builder<MediaList>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdministrator()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->where('visibility', self::VISIBILITY_SHARED)
                ->orWhere('owner_user_id', $user->getKey());
        });
    }

    public function visibilityLabel(): string
    {
        return self::visibilities()[$this->visibility] ?? $this->visibility;
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->owner_user_id === (int) $user->getKey();
    }
}
