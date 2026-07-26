<?php

namespace App\Livewire\Media;

use App\Models\Media;
use App\Services\Covers\CoverStorageService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Show extends Component
{
    public Media $media;

    public function mount(Media $media): void
    {
        Gate::authorize('view', $media);

        $this->media = $media;
    }

    public function render(CoverStorageService $covers): View
    {
        Gate::authorize('view', $this->media);

        $this->media->load([
            'library',
            'identifiers',
            'copies.location.parent.parent.parent',
            'copies.owners',
        ]);

        return view('livewire.media.show', [
            'coverUrl' => $covers->url($this->media),
        ]);
    }
}
