<?php

namespace App\Livewire\Media;

use App\Models\Copy;
use App\Models\Media;
use App\Models\User;
use App\Support\MediaCatalogSearch;
use App\Support\ResolvesCurrentLibrary;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use ResolvesCurrentLibrary;
    use WithPagination;

    public string $search = '';

    public string $type = 'all';

    public string $visibility = 'all';

    public string $copyStatus = 'all';

    public string $yearFrom = '';

    public string $yearTo = '';

    public string $sort = 'relevance';

    public function updated(
        string $property,
    ): void {
        if (in_array($property, [
            'search',
            'type',
            'visibility',
            'copyStatus',
            'yearFrom',
            'yearTo',
            'sort',
        ], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->type = 'all';
        $this->visibility = 'all';
        $this->copyStatus = 'all';
        $this->yearFrom = '';
        $this->yearTo = '';
        $this->sort = 'relevance';
        $this->resetPage();
    }

    public function render(
        MediaCatalogSearch $catalogSearch,
    ): View {
        $library = $this->currentLibrary();
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $search = trim($this->search);

        $query = Media::query()
            ->forLibrary($library)
            ->visibleTo($user);

        if ($search !== '') {
            $catalogSearch->apply($query, $search);
        }

        $this->applyFilters($query);
        $this->applySort($query, $search !== '');

        $mediaItems = $query
            ->withCount([
                'copies',
                'copies as available_copies_count' => function (Builder $query): void {
                    $query->where('status', Copy::STATUS_AVAILABLE);
                },
            ])
            ->orderBy('id')
            ->paginate(20);

        return view('livewire.media.index', [
            'library' => $library,
            'mediaItems' => $mediaItems,
            'types' => Media::types(),
            'visibilities' => Media::visibilities(),
            'copyStatuses' => [
                'all' => 'Alle Bestände',
                Copy::STATUS_AVAILABLE => 'Verfügbar',
                Copy::STATUS_LOANED => 'Ausgeliehen',
                Copy::STATUS_MISSING => 'Vermisst',
                Copy::STATUS_RETIRED => 'Ausgesondert',
                'without_copies' => 'Ohne Exemplar',
            ],
            'sortOptions' => [
                'relevance' => 'Relevanz',
                'title' => 'Titel A–Z',
                'year_desc' => 'Erscheinungsjahr absteigend',
                'year_asc' => 'Erscheinungsjahr aufsteigend',
                'created_desc' => 'Zuletzt angelegt',
            ],
        ]);
    }

    /**
     * @param  Builder<Media>  $query
     */
    private function applyFilters(Builder $query): void
    {
        if (array_key_exists($this->type, Media::types())) {
            $query->where('type', $this->type);
        }

        if (array_key_exists($this->visibility, Media::visibilities())) {
            $query->where('visibility', $this->visibility);
        }

        if (array_key_exists($this->copyStatus, Copy::statuses())) {
            $query->whereHas(
                'copies',
                function (Builder $query): void {
                    $query->where('status', $this->copyStatus);
                },
            );
        } elseif ($this->copyStatus === 'without_copies') {
            $query->doesntHave('copies');
        }

        $yearFrom = $this->year($this->yearFrom);
        $yearTo = $this->year($this->yearTo);

        if ($yearFrom !== null) {
            $query->where('publication_year', '>=', $yearFrom);
        }

        if ($yearTo !== null) {
            $query->where('publication_year', '<=', $yearTo);
        }
    }

    /**
     * @param  Builder<Media>  $query
     */
    private function applySort(
        Builder $query,
        bool $hasSearch,
    ): void {
        match ($this->sort) {
            'year_desc' => $query
                ->orderByRaw('publication_year IS NULL')
                ->orderByDesc('publication_year')
                ->orderByRaw('coalesce(sort_title, title)'),
            'year_asc' => $query
                ->orderByRaw('publication_year IS NULL')
                ->orderBy('publication_year')
                ->orderByRaw('coalesce(sort_title, title)'),
            'created_desc' => $query
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
            'title' => $query
                ->orderByRaw('coalesce(sort_title, title)'),
            default => $hasSearch
                ? $query
                    ->orderByDesc('search_rank')
                    ->orderByRaw('coalesce(sort_title, title)')
                : $query->orderByRaw('coalesce(sort_title, title)'),
        };
    }

    private function year(
        string $value,
    ): ?int {
        $value = trim($value);

        if (! preg_match('/^\d{1,4}$/', $value)) {
            return null;
        }

        $year = (int) $value;

        return $year >= 1 && $year <= 9999
            ? $year
            : null;
    }
}
