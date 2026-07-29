<?php

namespace App\Livewire\Lists;

use App\Models\MediaList;
use App\Models\User;
use App\Support\ResolvesCurrentLibrary;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Index extends Component
{
    use ResolvesCurrentLibrary;

    public string $name = '';

    public string $description = '';

    public string $visibility = MediaList::VISIBILITY_PRIVATE;

    public function mount(): void
    {
        Gate::authorize('viewAny', MediaList::class);
    }

    public function createList(): void
    {
        $library = $this->currentLibrary();

        Gate::authorize('create', [MediaList::class, $library]);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'visibility' => [
                'required',
                Rule::in(array_keys(MediaList::visibilities())),
            ],
        ]);

        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $mediaList = MediaList::query()->create([
            'library_id' => $library->getKey(),
            'owner_user_id' => $user->getKey(),
            'name' => trim($validated['name']),
            'description' => $this->nullableString($validated['description']),
            'visibility' => $validated['visibility'],
        ]);

        session()->flash('status', 'Liste wurde angelegt.');

        $this->redirectRoute('lists.show', [
            'mediaList' => $mediaList->getKey(),
        ]);
    }

    public function deleteList(int $mediaListId): void
    {
        $mediaList = MediaList::query()
            ->where('library_id', $this->currentLibrary()->getKey())
            ->findOrFail($mediaListId);

        Gate::authorize('delete', $mediaList);

        $mediaList->delete();

        session()->flash('status', 'Liste wurde gelöscht.');
    }

    public function render(): View
    {
        $library = $this->currentLibrary();
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return view('livewire.lists.index', [
            'library' => $library,
            'lists' => MediaList::query()
                ->where('library_id', $library->getKey())
                ->visibleTo($user)
                ->with('owner')
                ->withCount('items')
                ->orderBy('name')
                ->get(),
            'visibilities' => MediaList::visibilities(),
        ]);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
