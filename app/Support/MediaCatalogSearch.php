<?php

namespace App\Support;

use App\Models\Media;
use App\Models\MediaIdentifier;
use Illuminate\Database\Eloquent\Builder;

class MediaCatalogSearch
{
    /**
     * @param  Builder<Media>  $query
     * @return Builder<Media>
     */
    public function apply(Builder $query, string $search): Builder
    {
        $search = trim($search);
        $normalized = MediaIdentifier::normalize($search);
        $containsSearch = "%{$search}%";

        $query->where(function (Builder $query) use (
            $containsSearch,
            $normalized,
            $search,
        ): void {
            $query
                ->whereRaw(
                    "media.search_vector @@ websearch_to_tsquery('german', ?)",
                    [$search],
                )
                ->orWhere('title', 'ilike', $containsSearch)
                ->orWhere('subtitle', 'ilike', $containsSearch)
                ->orWhere('creators', 'ilike', $containsSearch)
                ->orWhere('publisher', 'ilike', $containsSearch)
                ->orWhere('publication_place', 'ilike', $containsSearch)
                ->orWhere('edition', 'ilike', $containsSearch)
                ->orWhere('description', 'ilike', $containsSearch);

            if ($normalized === '') {
                return;
            }

            $normalizedContains = "%{$normalized}%";

            $query
                ->orWhereHas(
                    'identifiers',
                    function (Builder $query) use ($normalizedContains): void {
                        $query->where(
                            'normalized_value',
                            'like',
                            $normalizedContains,
                        );
                    },
                )
                ->orWhereHas(
                    'copies',
                    function (Builder $query) use ($normalizedContains): void {
                        $query->where(function (Builder $query) use ($normalizedContains): void {
                            $query
                                ->whereRaw(
                                    "upper(regexp_replace(coalesce(inventory_code, ''), '[^A-Z0-9]', '', 'g')) LIKE ?",
                                    [$normalizedContains],
                                )
                                ->orWhereRaw(
                                    "upper(regexp_replace(coalesce(barcode, ''), '[^A-Z0-9]', '', 'g')) LIKE ?",
                                    [$normalizedContains],
                                );
                        });
                    },
                );
        });

        return $query->selectRaw(
            "media.*,
             ts_rank_cd(
                media.search_vector,
                websearch_to_tsquery('german', ?),
                32
             ) AS search_rank",
            [$search],
        );
    }
}
