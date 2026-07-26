<?php

namespace App\Support;

use App\Models\Library;
use App\Models\Media;
use App\Models\MediaIdentifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class MediaDuplicateFinder
{
    /**
     * @param  array<string, string>  $identifiers
     */
    public function find(
        Library $library,
        string $title,
        ?int $publicationYear,
        array $identifiers,
        ?Media $ignore = null,
    ): ?Media {
        $normalizedIdentifiers = collect($identifiers)
            ->map(static fn (string $value): string => MediaIdentifier::normalize($value))
            ->filter()
            ->values();

        if ($normalizedIdentifiers->isNotEmpty()) {
            $duplicate = Media::query()
                ->forLibrary($library)
                ->when(
                    $ignore !== null,
                    fn (Builder $query): Builder => $query->where(
                        $ignore->getQualifiedKeyName(),
                        '!=',
                        $ignore->getKey(),
                    ),
                )
                ->whereHas('identifiers', function (Builder $query) use ($normalizedIdentifiers): void {
                    $query->whereIn('normalized_value', $normalizedIdentifiers->all());
                })
                ->first();

            if ($duplicate !== null) {
                return $duplicate;
            }
        }

        $normalizedTitle = Str::lower(trim($title));

        if ($normalizedTitle === '' || $publicationYear === null) {
            return null;
        }

        return Media::query()
            ->forLibrary($library)
            ->when(
                $ignore !== null,
                fn (Builder $query): Builder => $query->where(
                    $ignore->getQualifiedKeyName(),
                    '!=',
                    $ignore->getKey(),
                ),
            )
            ->whereRaw('LOWER(title) = ?', [$normalizedTitle])
            ->where('publication_year', $publicationYear)
            ->first();
    }
}
