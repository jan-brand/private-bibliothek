<?php

namespace App\Livewire\Media;

use App\Models\Media;
use App\Models\MediaUserState;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ReadingStatus extends Component
{
    public Media $media;

    public string $status = '';

    public string $startedAt = '';

    public string $finishedAt = '';

    public function mount(Media $media): void
    {
        Gate::authorize('view', $media);

        $this->media = $media;

        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $state = MediaUserState::query()
            ->where('media_id', $media->getKey())
            ->where('user_id', $user->getKey())
            ->first();

        if ($state === null) {
            return;
        }

        $this->status = $state->status;
        $this->startedAt = $this->dateValue(
            $state->getRawOriginal('started_at'),
        );
        $this->finishedAt = $this->dateValue(
            $state->getRawOriginal('finished_at'),
        );
    }

    public function save(): void
    {
        Gate::authorize('view', $this->media);

        $validated = $this->validate([
            'status' => [
                'nullable',
                Rule::in(array_keys(MediaUserState::statuses())),
            ],
            'startedAt' => ['nullable', 'date'],
            'finishedAt' => ['nullable', 'date', 'after_or_equal:startedAt'],
        ]);

        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        if ($validated['status'] === '') {
            MediaUserState::query()
                ->where('media_id', $this->media->getKey())
                ->where('user_id', $user->getKey())
                ->delete();

            $this->startedAt = '';
            $this->finishedAt = '';

            session()->flash('status', 'Persönlicher Lesestatus wurde entfernt.');

            return;
        }

        MediaUserState::query()->updateOrCreate(
            [
                'media_id' => $this->media->getKey(),
                'user_id' => $user->getKey(),
            ],
            [
                'status' => $validated['status'],
                'started_at' => $this->nullableString($validated['startedAt']),
                'finished_at' => $this->nullableString($validated['finishedAt']),
            ],
        );

        session()->flash('status', 'Persönlicher Lesestatus wurde gespeichert.');
    }

    public function render(): View
    {
        Gate::authorize('view', $this->media);

        return view('livewire.media.reading-status', [
            'statuses' => MediaUserState::statuses(),
        ]);
    }

    private function dateValue(mixed $value): string
    {
        $value = trim((string) $value);

        return $value === '' ? '' : substr($value, 0, 10);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
