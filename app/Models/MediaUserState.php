<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $media_id
 * @property int $user_id
 * @property string $status
 */
class MediaUserState extends Model
{
    public const STATUS_UNREAD = 'unread';

    public const STATUS_READING = 'reading';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_FINISHED = 'finished';

    public const STATUS_ABANDONED = 'abandoned';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'media_id',
        'user_id',
        'status',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'media_id' => 'integer',
            'user_id' => 'integer',
            'started_at' => 'date',
            'finished_at' => 'date',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_UNREAD => 'Ungelesen',
            self::STATUS_READING => 'Begonnen',
            self::STATUS_PAUSED => 'Pausiert',
            self::STATUS_FINISHED => 'Beendet',
            self::STATUS_ABANDONED => 'Abgebrochen',
        ];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
