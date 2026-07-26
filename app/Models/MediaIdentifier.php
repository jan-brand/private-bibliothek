<?php

namespace App\Models;

use App\Support\IsbnDisplayFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MediaIdentifier extends Model
{
    public const SCHEME_ISBN = 'isbn';

    public const SCHEME_ISSN = 'issn';

    public const SCHEME_ZDB = 'zdb';

    public const SCHEME_OTHER = 'other';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'media_id',
        'scheme',
        'value',
        'normalized_value',
        'label',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $identifier): void {
            if ($identifier->scheme !== self::SCHEME_ISBN) {
                return;
            }

            $normalized = self::normalize((string) $identifier->value);

            $identifier->value = $normalized;
            $identifier->normalized_value = $normalized;
        });
    }

    public function displayValue(): string
    {
        return $this->scheme === self::SCHEME_ISBN
            ? IsbnDisplayFormatter::format((string) $this->value)
            : (string) $this->value;
    }

    protected function casts(): array
    {
        return [
            'media_id' => 'integer',
        ];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public static function normalize(string $value): string
    {
        return Str::upper((string) preg_replace('/[^A-Z0-9]/i', '', $value));
    }
}
