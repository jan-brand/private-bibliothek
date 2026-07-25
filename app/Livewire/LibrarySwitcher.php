<?php

namespace App\Livewire;

use App\Models\Library;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class LibrarySwitcher extends Component
{
    public ?int $selectedLibraryId = null;

    public function mount(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $sessionLibraryId = session('current_library_id');
        $library = Library::query()
            ->accessibleTo($user)
            ->find($sessionLibraryId);

        if ($library === null) {
            $library = Library::query()
                ->accessibleTo($user)
                ->orderBy('type')
                ->orderBy('name')
                ->first();
        }

        if ($library !== null) {
            $this->selectedLibraryId = (int) $library->getKey();
            session(['current_library_id' => $this->selectedLibraryId]);
        }
    }

    public function selectLibrary(int $libraryId): void
    {
        $library = Library::query()->findOrFail($libraryId);

        Gate::authorize('view', $library);

        $this->selectedLibraryId = (int) $library->getKey();
        session(['current_library_id' => $this->selectedLibraryId]);

        $this->dispatch('library-selected', libraryId: $this->selectedLibraryId);
    }

    public function render(): View
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return view('livewire.library-switcher', [
            'libraries' => Library::query()
                ->accessibleTo($user)
                ->orderBy('type')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
