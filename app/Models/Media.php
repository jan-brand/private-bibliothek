<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Media extends Model
{
    public const TYPE_BOOK = 'book';

    public const TYPE_MAGAZINE_ISSUE = 'magazine_issue';

    public const TYPE_BROCHURE = 'brochure';

    public const TYPE_OTHER = 'other';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'library_id',
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

    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }

    public function identifiers(): HasMany
    {
        return $this->hasMany(MediaIdentifier::class);
    }

    public function copies(): HasMany
    {
        return $this->hasMany(Copy::class);
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

    public function typeLabel(): string
    {
        return self::types()[$this->type] ?? $this->type;
    }
}
