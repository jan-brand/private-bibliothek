<?php

namespace App\Livewire\Copies;

use App\Models\Copy;
use App\Models\User;
use App\Support\IsbnDisplayFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class BarcodeScanner extends Component
{
    public Copy $copy;

    public string $barcode = '';

    public function mount(Copy $copy): void
    {
        Gate::authorize('update', $copy);

        $this->copy = $copy;
        $this->barcode = IsbnDisplayFormatter::format($copy->barcode);
    }

    public function capture(string $barcode): void
    {
        $this->barcode = $barcode;
        $this->save();
    }

    public function save(): void
    {
        Gate::authorize('update', $this->copy);

        $validated = $this->validate([
            'barcode' => ['required', 'string', 'max:255'],
        ]);

        $normalized = IsbnDisplayFormatter::normalizeBarcode(
            $validated['barcode'],
        );

        if ($normalized === null) {
            $this->addError('barcode', 'Der Barcode ist leer.');

            return;
        }

        if (
            Copy::query()
                ->where('library_id', $this->copy->library_id)
                ->where('barcode', $normalized)
                ->whereKeyNot($this->copy->getKey())
                ->exists()
        ) {
            $this->addError(
                'barcode',
                'Dieser Barcode ist in der Bibliothek bereits vergeben.',
            );

            return;
        }

        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $this->copy->update([
            'barcode' => $normalized,
            'updated_by_user_id' => $user->getKey(),
        ]);

        $this->barcode = IsbnDisplayFormatter::format($normalized);

        session()->flash('status', 'Barcode wurde gespeichert.');

        $this->redirectRoute(
            'copies.edit',
            ['copy' => $this->copy->getKey()],
        );
    }

    public function formatBarcode(): void
    {
        $this->barcode = IsbnDisplayFormatter::format($this->barcode);
    }

    public function render(): View
    {
        Gate::authorize('update', $this->copy);

        return view('livewire.copies.barcode-scanner');
    }
}
