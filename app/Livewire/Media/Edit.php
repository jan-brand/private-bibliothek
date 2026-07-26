<?php

namespace App\Livewire\Media;

use App\Models\Library;
use App\Models\Media;
use App\Models\MediaIdentifier;
use App\Models\User;
use App\Support\MediaDuplicateFinder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Edit extends Component
{
    public Media $media;

    public string $type = Media::TYPE_BOOK;

    public string $title = '';

    public string $subtitle = '';

    public string $sortTitle = '';

    public string $creators = '';

    public string $publisher = '';

    public string $publicationPlace = '';

    public string $publicationYear = '';

    public string $edition = '';

    public string $languageCode = '';

    public string $description = '';

    public string $coverUrl = '';

    public string $isbn = '';

    public string $issn = '';

    public ?int $duplicateMediaId = null;

    public string $duplicateMessage = '';

    public bool $duplicateConfirmed = false;

    public function mount(Media $media): void
    {
        Gate::authorize('update', $media);

        $media->load('identifiers');

        $this->media = $media;
        $this->type = $media->type;
        $this->title = $media->title;
        $this->subtitle = $media->subtitle ?? '';
        $this->sortTitle = $media->sort_title ?? '';
        $this->creators = $media->creators ?? '';
        $this->publisher = $media->publisher ?? '';
        $this->publicationPlace = $media->publication_place ?? '';
        $this->publicationYear = $media->publication_year !== null
            ? (string) $media->publication_year
            : '';
        $this->edition = $media->edition ?? '';
        $this->languageCode = $media->language_code ?? '';
        $this->description = $media->description ?? '';
        $this->coverUrl = $media->cover_url ?? '';
        $this->isbn = (string) (
            $media->identifiers()
                ->where('scheme', MediaIdentifier::SCHEME_ISBN)
                ->value('value') ?? ''
        );
        $this->issn = (string) (
            $media->identifiers()
                ->where('scheme', MediaIdentifier::SCHEME_ISSN)
                ->value('value') ?? ''
        );
    }

    public function save(): void
    {
        Gate::authorize('update', $this->media);

        $library = $this->media->library;
        abort_unless($library instanceof Library, 404);

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
                $this->media,
            );

            if ($duplicate !== null) {
                $this->duplicateMediaId = (int) $duplicate->getKey();
                $this->duplicateMessage = "Möglicher Dublettentreffer: {$duplicate->title}";

                return;
            }
        }

        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        DB::transaction(function () use ($user, $validated): void {
            $this->media->update([
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
                'updated_by_user_id' => $user->getKey(),
            ]);

            $this->syncIdentifier(MediaIdentifier::SCHEME_ISBN, $validated['isbn'], 'ISBN');
            $this->syncIdentifier(MediaIdentifier::SCHEME_ISSN, $validated['issn'], 'ISSN');
        });

        session()->flash('status', 'Medium wurde aktualisiert.');

        $this->redirectRoute('media.show', ['media' => $this->media->getKey()]);
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

    public function delete(): void
    {
        Gate::authorize('delete', $this->media);

        if ($this->media->copies()->exists()) {
            $this->addError(
                'delete',
                'Das Medium kann erst gelöscht werden, wenn alle Exemplare gelöscht wurden.',
            );

            return;
        }

        $this->media->delete();

        session()->flash('status', 'Medium wurde gelöscht.');

        $this->redirectRoute('media.index');
    }

    public function render(): View
    {
        Gate::authorize('update', $this->media);

        return view('livewire.media.edit', [
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

    private function publicationYearValue(mixed $value): ?int
    {
        return $value === '' || $value === null ? null : (int) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function syncIdentifier(string $scheme, mixed $value, string $label): void
    {
        $value = trim((string) $value);

        if ($value === '') {
            $this->media->identifiers()->where('scheme', $scheme)->delete();

            return;
        }

        $this->media->identifiers()->updateOrCreate(
            ['scheme' => $scheme],
            [
                'value' => $value,
                'normalized_value' => MediaIdentifier::normalize($value),
                'label' => $label,
            ],
        );
    }
}
