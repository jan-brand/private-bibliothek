<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $media_list_id
 * @property int $media_id
 * @property int $position
 */
class MediaListItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'media_list_id',
        'media_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'media_list_id' => 'integer',
            'media_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function mediaList(): BelongsTo
    {
        return $this->belongsTo(MediaList::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
