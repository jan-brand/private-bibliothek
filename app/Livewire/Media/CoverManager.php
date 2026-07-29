<?php

namespace App\Livewire\Media;

use App\Models\Media;
use App\Services\Covers\CoverStorageService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Throwable;

class CoverManager extends Component
{
    use WithFileUploads;

    public Media $media;

    public ?TemporaryUploadedFile $upload = null;

    public string $remoteUrl = '';

    public string $errorMessage = '';

    public function mount(Media $media): void
    {
        Gate::authorize('update', $media);

        $this->media = $media;
        $this->remoteUrl = trim(
            (string) $media->getAttribute('cover_source_url'),
        );
    }

    public function storeUpload(CoverStorageService $covers): void
    {
        Gate::authorize('update', $this->media);

        $this->validate([
            'upload' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp,gif',
                'max:5120',
            ],
        ]);

        $this->errorMessage = '';

        try {
            $covers->storeUpload(
                $this->media,
                $this->upload,
            );
        } catch (Throwable $exception) {
            Log::warning('Cover upload failed.', [
                'media_id' => $this->media->getKey(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            $this->errorMessage =
                'Das Cover konnte nicht gespeichert werden.';

            return;
        }

        $this->upload = null;
        $this->media->refresh();

        session()->flash('status', 'Cover wurde gespeichert.');
    }

    public function importRemote(CoverStorageService $covers): void
    {
        Gate::authorize('update', $this->media);

        $validated = $this->validate([
            'remoteUrl' => ['required', 'url', 'max:2048'],
        ]);

        $this->errorMessage = '';

        try {
            $covers->importRemote(
                $this->media,
                $validated['remoteUrl'],
            );
        } catch (Throwable $exception) {
            Log::warning('Remote cover import failed.', [
                'media_id' => $this->media->getKey(),
                'url' => $validated['remoteUrl'],
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            $this->errorMessage =
                'Das Cover konnte nicht von der Adresse geladen werden.';

            return;
        }

        $this->media->refresh();

        session()->flash(
            'status',
            'Cover wurde lokal gespeichert.',
        );
    }

    public function removeLocal(CoverStorageService $covers): void
    {
        Gate::authorize('update', $this->media);

        $covers->removeLocal($this->media);
        $this->media->refresh();

        session()->flash(
            'status',
            'Lokales Cover wurde entfernt.',
        );
    }

    public function render(CoverStorageService $covers): View
    {
        Gate::authorize('update', $this->media);

        return view('livewire.media.cover-manager', [
            'coverUrl' => $covers->url($this->media),
            'hasLocalCover' => trim(
                (string) $this->media->getAttribute('cover_path'),
            ) !== '',
        ]);
    }
}
