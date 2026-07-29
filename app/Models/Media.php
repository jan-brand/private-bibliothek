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
 * @property string $visibility
 * @property string $type
 * @property string $title
 * @property string|null $subtitle
 * @property string|null $sort_title
 * @property string|null $creators
 * @property string|null $publisher
 * @property string|null $publication_place
 * @property int|null $publication_year
 * @property string|null $edition
 * @property string|null $language_code
 * @property string|null $description
 * @property string|null $cover_url
 * @property int $created_by_user_id
 * @property int|null $updated_by_user_id
 */
class Media extends Model
{
    public const TYPE_BOOK = 'book';

    public const TYPE_MAGAZINE_ISSUE = 'magazine_issue';

    public const TYPE_BROCHURE = 'brochure';

    public const TYPE_OTHER = 'other';

    public const VISIBILITY_SHARED = 'shared';

    public const VISIBILITY_PRIVATE = 'private';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'library_id',
        'owner_user_id',
        'visibility',
        'type',
        'title',
        'subtitle',
        'sort_title',
        'creators',
        'publisher',
        'publication_place',
        'publication_year',
        'edition',
        'language_code',
        'description',
        'cover_url',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'library_id' => 'integer',
            'owner_user_id' => 'integer',
            'publication_year' => 'integer',
            'created_by_user_id' => 'integer',
            'updated_by_user_id' => 'integer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_BOOK => 'Buch',
            self::TYPE_MAGAZINE_ISSUE => 'Zeitschriftenheft',
            self::TYPE_BROCHURE => 'Broschüre',
            self::TYPE_OTHER => 'Sonstiges Medium',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function visibilities(): array
    {
        return [
            self::VISIBILITY_SHARED => 'Gemeinsam',
            self::VISIBILITY_PRIVATE => 'Privat',
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

    public function identifiers(): HasMany
    {
        return $this->hasMany(MediaIdentifier::class);
    }

    public function copies(): HasMany
    {
        return $this->hasMany(Copy::class);
    }

    public function listItems(): HasMany
    {
        return $this->hasMany(MediaListItem::class);
    }

    public function userStates(): HasMany
    {
        return $this->hasMany(MediaUserState::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /**
     * @param  Builder<Media>  $query
     * @return Builder<Media>
     */
    public function scopeForLibrary(Builder $query, Library $library): Builder
    {
        return $query->where('library_id', $library->getKey());
    }

    /**
     * @param  Builder<Media>  $query
     * @return Builder<Media>
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

    public function typeLabel(): string
    {
        return self::types()[$this->type] ?? $this->type;
    }

    public function visibilityLabel(): string
    {
        return self::visibilities()[$this->visibility] ?? $this->visibility;
    }

    public function isPrivate(): bool
    {
        return $this->visibility === self::VISIBILITY_PRIVATE;
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->owner_user_id === (int) $user->getKey();
    }
}
