<?php

namespace App\Livewire;

use App\Models\Media;
use App\Models\User;
use App\Support\ResolvesCurrentLibrary;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    use ResolvesCurrentLibrary;

    public function render(): View
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $library = $this->currentLibrary();

        return view('livewire.dashboard', [
            'library' => $library,
            'user' => $user,
            'visibleMediaCount' => Media::query()
                ->forLibrary($library)
                ->visibleTo($user)
                ->count(),
            'privateMediaCount' => Media::query()
                ->forLibrary($library)
                ->where('owner_user_id', $user->getKey())
                ->where('visibility', Media::VISIBILITY_PRIVATE)
                ->count(),
        ]);
    }
}
