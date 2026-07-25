<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Copy extends Model
{
    public const CONDITION_NEW = 'new';

    public const CONDITION_GOOD = 'good';

    public const CONDITION_USED = 'used';

    public const CONDITION_DAMAGED = 'damaged';

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_LOANED = 'loaned';

    public const STATUS_MISSING = 'missing';

    public const STATUS_RETIRED = 'retired';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'library_id',
        'media_id',
        'location_id',
        'inventory_code',
        'barcode',
        'condition',
        'status',
        'acquired_at',
        'acquisition_source',
        'purchase_price',
        'notes',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'library_id' => 'integer',
            'media_id' => 'integer',
            'location_id' => 'integer',
            'acquired_at' => 'date',
            'purchase_price' => 'decimal:2',
            'created_by_user_id' => 'integer',
            'updated_by_user_id' => 'integer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function conditions(): array
    {
        return [
            self::CONDITION_NEW => 'Neu',
            self::CONDITION_GOOD => 'Gut',
            self::CONDITION_USED => 'Gebraucht',
            self::CONDITION_DAMAGED => 'Beschädigt',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_AVAILABLE => 'Verfügbar',
            self::STATUS_LOANED => 'Ausgeliehen',
            self::STATUS_MISSING => 'Vermisst',
            self::STATUS_RETIRED => 'Ausgesondert',
        ];
    }

    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function ownerships(): HasMany
    {
        return $this->hasMany(CopyOwner::class);
    }

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'copy_owners')
            ->withTimestamps();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function conditionLabel(): string
    {
        return self::conditions()[$this->condition] ?? $this->condition;
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }
}
