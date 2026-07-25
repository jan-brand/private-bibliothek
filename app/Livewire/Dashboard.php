<?php

namespace App\Livewire;

use App\Models\Library;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class Dashboard extends Component
{
    public function mount(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $selectedLibrary = Library::query()
            ->accessibleTo($user)
            ->find(session('current_library_id'));

        if ($selectedLibrary === null) {
            $selectedLibrary = Library::query()
                ->accessibleTo($user)
                ->orderBy('type')
                ->orderBy('name')
                ->first();
        }

        if ($selectedLibrary !== null) {
            session(['current_library_id' => $selectedLibrary->getKey()]);
        }
    }

    #[On('library-selected')]
    public function librarySelected(int $libraryId): void
    {
        session(['current_library_id' => $libraryId]);
    }

    public function render(): View
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $selectedLibrary = Library::query()
            ->accessibleTo($user)
            ->find(session('current_library_id'));

        return view('livewire.dashboard', [
            'selectedLibrary' => $selectedLibrary,
            'user' => $user,
        ]);
    }
}
