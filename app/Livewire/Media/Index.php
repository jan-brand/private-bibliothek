<?php

namespace App\Livewire\Media;

use App\Models\Media;
use App\Support\ResolvesCurrentLibrary;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use ResolvesCurrentLibrary;
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $library = $this->currentLibrary();
        $search = trim($this->search);

        $mediaItems = Media::query()
            ->forLibrary($library)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'ilike', "%{$search}%")
                        ->orWhere('subtitle', 'ilike', "%{$search}%")
                        ->orWhere('creators', 'ilike', "%{$search}%")
                        ->orWhere('publisher', 'ilike', "%{$search}%")
                        ->orWhereHas('identifiers', function ($query) use ($search): void {
                            $query->where('value', 'ilike', "%{$search}%")
                                ->orWhere('normalized_value', 'ilike', "%{$search}%");
                        });
                });
            })
            ->withCount('copies')
            ->orderByRaw('coalesce(sort_title, title)')
            ->orderBy('id')
            ->paginate(20);

        return view('livewire.media.index', [
            'library' => $library,
            'mediaItems' => $mediaItems,
        ]);
    }
}
