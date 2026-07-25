<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CopyOwner extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'copy_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'copy_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    public function copy(): BelongsTo
    {
        return $this->belongsTo(Copy::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
