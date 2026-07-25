<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    public const TYPE_APARTMENT = 'apartment';

    public const TYPE_ROOM = 'room';

    public const TYPE_SHELF = 'shelf';

    public const TYPE_BOARD = 'board';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'library_id',
        'parent_id',
        'type',
        'name',
        'sort_order',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'library_id' => 'integer',
            'parent_id' => 'integer',
            'sort_order' => 'integer',
            'created_by_user_id' => 'integer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_APARTMENT => 'Wohnung',
            self::TYPE_ROOM => 'Raum',
            self::TYPE_SHELF => 'Regal',
            self::TYPE_BOARD => 'Regalbrett',
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public static function requiredParentTypes(): array
    {
        return [
            self::TYPE_APARTMENT => null,
            self::TYPE_ROOM => self::TYPE_APARTMENT,
            self::TYPE_SHELF => self::TYPE_ROOM,
            self::TYPE_BOARD => self::TYPE_SHELF,
        ];
    }

    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function copies(): HasMany
    {
        return $this->hasMany(Copy::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function typeLabel(): string
    {
        return self::types()[$this->type] ?? $this->type;
    }

    public function breadcrumb(): string
    {
        $parts = [$this->name];
        $parent = $this->parent;

        while ($parent !== null) {
            array_unshift($parts, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' › ', $parts);
    }
}
