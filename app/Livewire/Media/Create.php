<?php

namespace App\Livewire\Media;

use App\Models\Media;
use App\Models\MediaIdentifier;
use App\Models\User;
use App\Support\MediaDuplicateFinder;
use App\Support\ResolvesCurrentLibrary;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Create extends Component
{
    use ResolvesCurrentLibrary;

    public string $type = Media::TYPE_BOOK;

    public string $title = '';

    public string $subtitle = '';

    public string $sortTitle = '';

    public string $creators = '';

    public string $publisher = '';

    public string $publicationPlace = '';

    public string $publicationYear = '';

    public string $edition = '';

    public string $languageCode = 'de';

    public string $description = '';

    public string $coverUrl = '';

    public string $isbn = '';

    public string $issn = '';

    public ?int $duplicateMediaId = null;

    public string $duplicateMessage = '';

    public bool $duplicateConfirmed = false;

    public function mount(): void
    {
        Gate::authorize('create', [Media::class, $this->currentLibrary()]);

        $import = session()->pull('media_import');

        if (is_array($import)) {
            $this->fillFromImport($import);
        }
    }

    public function save(): void
    {
        $library = $this->currentLibrary();
        Gate::authorize('create', [Media::class, $library]);

        $validated = $this->validatedData();

        if (! $this->duplicateConfirmed) {
            $duplicate = app(MediaDuplicateFinder::class)->find(
                $library,
                $validated['title'],
                $this->publicationYearValue($validated['publicationYear']),
                [
                    MediaIdentifier::SCHEME_ISBN => $validated['isbn'],
                    MediaIdentifier::SCHEME_ISSN => $validated['issn'],
                ],
            );

            if ($duplicate !== null) {
                $this->duplicateMediaId = (int) $duplicate->getKey();
                $this->duplicateMessage = "Möglicher Dublettentreffer: {$duplicate->title}";

                return;
            }
        }

        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $media = DB::transaction(function () use ($library, $user, $validated): Media {
            $media = Media::query()->create([
                'library_id' => $library->getKey(),
                'type' => $validated['type'],
                'title' => trim($validated['title']),
                'subtitle' => $this->nullableString($validated['subtitle']),
                'sort_title' => $this->nullableString($validated['sortTitle']),
                'creators' => $this->nullableString($validated['creators']),
                'publisher' => $this->nullableString($validated['publisher']),
                'publication_place' => $this->nullableString($validated['publicationPlace']),
                'publication_year' => $this->publicationYearValue($validated['publicationYear']),
                'edition' => $this->nullableString($validated['edition']),
                'language_code' => $this->nullableString($validated['languageCode']),
                'description' => $this->nullableString($validated['description']),
                'cover_url' => $this->nullableString($validated['coverUrl']),
                'created_by_user_id' => $user->getKey(),
                'updated_by_user_id' => $user->getKey(),
            ]);

            $this->createIdentifier(
                $media,
                MediaIdentifier::SCHEME_ISBN,
                $validated['isbn'],
                'ISBN',
            );
            $this->createIdentifier(
                $media,
                MediaIdentifier::SCHEME_ISSN,
                $validated['issn'],
                'ISSN',
            );

            return $media;
        });

        session()->flash('status', 'Medium wurde angelegt.');

        $this->redirectRoute('media.show', ['media' => $media->getKey()]);
    }

    public function confirmDuplicateAndSave(): void
    {
        $this->duplicateConfirmed = true;
        $this->save();
    }

    public function cancelDuplicateWarning(): void
    {
        $this->duplicateMediaId = null;
        $this->duplicateMessage = '';
        $this->duplicateConfirmed = false;
    }

    public function render(): View
    {
        return view('livewire.media.create', [
            'library' => $this->currentLibrary(),
            'types' => Media::types(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(): array
    {
        return $this->validate([
            'type' => ['required', Rule::in(array_keys(Media::types()))],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'sortTitle' => ['nullable', 'string', 'max:255'],
            'creators' => ['nullable', 'string', 'max:4000'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'publicationPlace' => ['nullable', 'string', 'max:255'],
            'publicationYear' => ['nullable', 'integer', 'min:1000', 'max:2100'],
            'edition' => ['nullable', 'string', 'max:255'],
            'languageCode' => ['nullable', 'string', 'max:8'],
            'description' => ['nullable', 'string', 'max:20000'],
            'coverUrl' => ['nullable', 'url', 'max:2048'],
            'isbn' => ['nullable', 'string', 'max:32'],
            'issn' => ['nullable', 'string', 'max:32'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $import
     */
    private function fillFromImport(array $import): void
    {
        $identifiers = is_array($import['identifiers'] ?? null)
            ? $import['identifiers']
            : [];

        $creators = is_array($import['creators'] ?? null)
            ? array_filter(
                $import['creators'],
                static fn (mixed $creator): bool => is_string($creator),
            )
            : [];

        $this->title = (string) ($import['title'] ?? '');
        $this->subtitle = (string) ($import['subtitle'] ?? '');
        $this->creators = implode('; ', $creators);
        $this->publisher = (string) ($import['publisher'] ?? '');
        $this->publicationPlace = (string) ($import['publication_place'] ?? '');
        $this->publicationYear = isset($import['publication_year'])
            ? (string) $import['publication_year']
            : '';
        $this->edition = (string) ($import['edition'] ?? '');
        $this->languageCode = (string) ($import['language_code'] ?? 'de');
        $this->description = (string) ($import['description'] ?? '');
        $this->isbn = is_string($identifiers['isbn'] ?? null)
            ? $identifiers['isbn']
            : '';
        $this->issn = is_string($identifiers['issn'] ?? null)
            ? $identifiers['issn']
            : '';

        if ($this->issn !== '' && $this->isbn === '') {
            $this->type = Media::TYPE_MAGAZINE_ISSUE;
        }
    }

    private function publicationYearValue(mixed $value): ?int
    {
        return $value === '' || $value === null ? null : (int) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function createIdentifier(
        Media $media,
        string $scheme,
        mixed $value,
        string $label,
    ): void {
        $value = trim((string) $value);

        if ($value === '') {
            return;
        }

        MediaIdentifier::query()->create([
            'media_id' => $media->getKey(),
            'scheme' => $scheme,
            'value' => $value,
            'normalized_value' => MediaIdentifier::normalize($value),
            'label' => $label,
        ]);
    }
}
