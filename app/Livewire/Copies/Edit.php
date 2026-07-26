<?php

namespace App\Livewire\Copies;

use App\Models\Copy;
use App\Models\Library;
use App\Models\Location;
use App\Models\User;
use App\Support\IsbnDisplayFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Edit extends Component
{
    public Copy $copy;

    public string $locationId = '';

    public string $inventoryCode = '';

    public string $barcode = '';

    public string $condition = Copy::CONDITION_GOOD;

    public string $status = Copy::STATUS_AVAILABLE;

    public string $acquiredAt = '';

    public string $acquisitionSource = '';

    public string $purchasePrice = '';

    public string $notes = '';

    /**
     * @var list<int|string>
     */
    public array $ownerUserIds = [];

    public function mount(Copy $copy): void
    {
        Gate::authorize('update', $copy);

        $copy->load('owners', 'library', 'media');

        $this->copy = $copy;
        $this->locationId = $copy->location_id !== null ? (string) $copy->location_id : '';
        $this->inventoryCode = $copy->inventory_code ?? '';
        $this->barcode = IsbnDisplayFormatter::format($copy->barcode);
        $this->condition = $copy->condition;
        $this->status = $copy->status;
        $this->acquiredAt = $copy->acquired_at !== null
            ? substr((string) $copy->acquired_at, 0, 10)
            : '';
        $this->acquisitionSource = $copy->acquisition_source ?? '';
        $this->purchasePrice = $copy->purchase_price !== null
            ? (string) $copy->purchase_price
            : '';
        $this->notes = $copy->notes ?? '';
        $this->ownerUserIds = $copy->owners->modelKeys();
    }

    public function save(): void
    {
        Gate::authorize('update', $this->copy);

        $library = $this->copy->library;
        abort_unless($library instanceof Library, 404);

        $validated = $this->validate([
            'locationId' => ['nullable', 'integer'],
            'inventoryCode' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'condition' => ['required', Rule::in(array_keys(Copy::conditions()))],
            'status' => ['required', Rule::in(array_keys(Copy::statuses()))],
            'acquiredAt' => ['nullable', 'date'],
            'acquisitionSource' => ['nullable', 'string', 'max:255'],
            'purchasePrice' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'notes' => ['nullable', 'string', 'max:20000'],
            'ownerUserIds' => ['required', 'array', 'min:1'],
            'ownerUserIds.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        $locationId = $validated['locationId'] !== ''
            ? (int) $validated['locationId']
            : null;

        if (
            $locationId !== null
            && ! Location::query()
                ->whereKey($locationId)
                ->where('library_id', $library->getKey())
                ->exists()
        ) {
            $this->addError('locationId', 'Der Standort gehört nicht zur Bibliothek.');

            return;
        }

        $ownerIds = collect($validated['ownerUserIds'])
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        $allowedOwnerIds = $library->users()
            ->whereIn('users.id', $ownerIds->all())
            ->pluck('users.id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->sort()
            ->values();

        if ($allowedOwnerIds->all() !== $ownerIds->sort()->values()->all()) {
            $this->addError(
                'ownerUserIds',
                'Alle Eigentümer müssen Mitglieder der Bibliothek sein.',
            );

            return;
        }

        $inventoryCode = $this->nullableString($validated['inventoryCode']);
        $barcode = IsbnDisplayFormatter::normalizeBarcode($validated['barcode']);

        if (
            $inventoryCode !== null
            && Copy::query()
                ->where('library_id', $library->getKey())
                ->where('inventory_code', $inventoryCode)
                ->whereKeyNot($this->copy->getKey())
                ->exists()
        ) {
            $this->addError('inventoryCode', 'Diese Inventarnummer ist bereits vergeben.');

            return;
        }

        if (
            $barcode !== null
            && Copy::query()
                ->where('library_id', $library->getKey())
                ->where('barcode', $barcode)
                ->whereKeyNot($this->copy->getKey())
                ->exists()
        ) {
            $this->addError('barcode', 'Dieser Barcode ist bereits vergeben.');

            return;
        }

        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        DB::transaction(function () use (
            $barcode,
            $inventoryCode,
            $locationId,
            $ownerIds,
            $user,
            $validated,
        ): void {
            $this->copy->update([
                'location_id' => $locationId,
                'inventory_code' => $inventoryCode,
                'barcode' => $barcode,
                'condition' => $validated['condition'],
                'status' => $validated['status'],
                'acquired_at' => $this->nullableString($validated['acquiredAt']),
                'acquisition_source' => $this->nullableString($validated['acquisitionSource']),
                'purchase_price' => $validated['purchasePrice'] !== ''
                    ? $validated['purchasePrice']
                    : null,
                'notes' => $this->nullableString($validated['notes']),
                'updated_by_user_id' => $user->getKey(),
            ]);

            $this->copy->owners()->sync($ownerIds->all());
        });

        session()->flash('status', 'Exemplar wurde aktualisiert.');

        $this->redirectRoute('media.show', ['media' => $this->copy->media_id]);
    }

    public function delete(): void
    {
        Gate::authorize('delete', $this->copy);

        $mediaId = $this->copy->media_id;
        $this->copy->delete();

        session()->flash('status', 'Exemplar wurde gelöscht.');

        $this->redirectRoute('media.show', ['media' => $mediaId]);
    }

    public function formatBarcode(): void
    {
        $this->barcode = IsbnDisplayFormatter::format($this->barcode);
    }

    public function render(): View
    {
        Gate::authorize('update', $this->copy);

        $library = $this->copy->library;
        abort_unless($library instanceof Library, 404);

        return view('livewire.copies.edit', [
            'locations' => Location::query()
                ->where('library_id', $library->getKey())
                ->with('parent.parent.parent')
                ->orderBy('type')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'members' => $library->users()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'conditions' => Copy::conditions(),
            'statuses' => Copy::statuses(),
        ]);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
