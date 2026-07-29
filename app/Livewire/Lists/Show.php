<?php

namespace App\Livewire\Lists;

use App\Models\Media;
use App\Models\MediaList;
use App\Models\MediaListItem;
use App\Models\User;
use App\Support\ResolvesCurrentLibrary;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Show extends Component
{
    use ResolvesCurrentLibrary;

    public MediaList $mediaList;

    public string $name = '';

    public string $description = '';

    public string $visibility = MediaList::VISIBILITY_PRIVATE;

    public string $mediaId = '';

    public function mount(MediaList $mediaList): void
    {
        abort_unless(
            (int) $mediaList->library_id
                === (int) $this->currentLibrary()->getKey(),
            404,
        );

        Gate::authorize('view', $mediaList);

        $this->mediaList = $mediaList;
        $this->name = $mediaList->name;
        $this->description = $mediaList->description ?? '';
        $this->visibility = $mediaList->visibility;
    }

    public function saveList(): void
    {
        Gate::authorize('update', $this->mediaList);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'visibility' => [
                'required',
                Rule::in(array_keys(MediaList::visibilities())),
            ],
        ]);

        if (
            $validated['visibility'] === MediaList::VISIBILITY_SHARED
            && $this->containsPrivateMedia()
        ) {
            $this->addError(
                'visibility',
                'Eine gemeinsame Liste darf keine privaten Medien enthalten.',
            );

            return;
        }

        $this->mediaList->update([
            'name' => trim($validated['name']),
            'description' => $this->nullableString($validated['description']),
            'visibility' => $validated['visibility'],
        ]);

        session()->flash('status', 'Liste wurde aktualisiert.');
    }

    public function addMedia(): void
    {
        Gate::authorize('update', $this->mediaList);

        $validated = $this->validate([
            'mediaId' => ['required', 'integer'],
        ]);

        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $mediaQuery = Media::query()
            ->where('library_id', $this->mediaList->library_id)
            ->visibleTo($user);

        if ($this->mediaList->visibility === MediaList::VISIBILITY_SHARED) {
            $mediaQuery->where(
                'visibility',
                Media::VISIBILITY_SHARED,
            );
        }

        $media = $mediaQuery->findOrFail((int) $validated['mediaId']);

        if (
            MediaListItem::query()
                ->where('media_list_id', $this->mediaList->getKey())
                ->where('media_id', $media->getKey())
                ->exists()
        ) {
            $this->addError('mediaId', 'Dieses Medium ist bereits auf der Liste.');

            return;
        }

        $position = (int) (
            MediaListItem::query()
                ->where('media_list_id', $this->mediaList->getKey())
                ->max('position') ?? 0
        );

        MediaListItem::query()->create([
            'media_list_id' => $this->mediaList->getKey(),
            'media_id' => $media->getKey(),
            'position' => $position + 1,
        ]);

        $this->mediaId = '';

        session()->flash('status', 'Medium wurde zur Liste hinzugefügt.');
    }

    public function moveItem(int $itemId, string $direction): void
    {
        Gate::authorize('update', $this->mediaList);

        abort_unless(in_array($direction, ['up', 'down'], true), 422);

        DB::transaction(function () use ($itemId, $direction): void {
            $item = MediaListItem::query()
                ->where('media_list_id', $this->mediaList->getKey())
                ->lockForUpdate()
                ->findOrFail($itemId);

            $other = MediaListItem::query()
                ->where('media_list_id', $this->mediaList->getKey())
                ->where(
                    'position',
                    $direction === 'up' ? '<' : '>',
                    $item->position,
                )
                ->orderBy(
                    'position',
                    $direction === 'up' ? 'desc' : 'asc',
                )
                ->lockForUpdate()
                ->first();

            if ($other === null) {
                return;
            }

            $position = $item->position;

            $item->update([
                'position' => $other->position,
            ]);

            $other->update([
                'position' => $position,
            ]);
        });
    }

    public function removeItem(int $itemId): void
    {
        Gate::authorize('update', $this->mediaList);

        MediaListItem::query()
            ->where('media_list_id', $this->mediaList->getKey())
            ->findOrFail($itemId)
            ->delete();

        $this->normalizePositions();

        session()->flash('status', 'Medium wurde von der Liste entfernt.');
    }

    public function deleteList(): void
    {
        Gate::authorize('delete', $this->mediaList);

        $this->mediaList->delete();

        session()->flash('status', 'Liste wurde gelöscht.');

        $this->redirectRoute('lists.index');
    }

    public function render(): View
    {
        Gate::authorize('view', $this->mediaList);

        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $visibleMediaIds = Media::query()
            ->visibleTo($user)
            ->select('id');

        $items = MediaListItem::query()
            ->where('media_list_id', $this->mediaList->getKey())
            ->whereIn('media_id', $visibleMediaIds)
            ->with('media')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $availableMedia = Media::query()
            ->where('library_id', $this->mediaList->library_id)
            ->visibleTo($user)
            ->whereNotIn('id', $items->pluck('media_id')->all());

        if ($this->mediaList->visibility === MediaList::VISIBILITY_SHARED) {
            $availableMedia->where(
                'visibility',
                Media::VISIBILITY_SHARED,
            );
        }

        return view('livewire.lists.show', [
            'items' => $items,
            'availableMedia' => $availableMedia
                ->orderBy('sort_title')
                ->orderBy('title')
                ->get(),
            'visibilities' => MediaList::visibilities(),
        ]);
    }

    private function containsPrivateMedia(): bool
    {
        return MediaListItem::query()
            ->where('media_list_id', $this->mediaList->getKey())
            ->whereIn(
                'media_id',
                Media::query()
                    ->where('visibility', Media::VISIBILITY_PRIVATE)
                    ->select('id'),
            )
            ->exists();
    }

    private function normalizePositions(): void
    {
        MediaListItem::query()
            ->where('media_list_id', $this->mediaList->getKey())
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->each(function (MediaListItem $item, int $index): void {
                $item->update([
                    'position' => $index + 1,
                ]);
            });
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
