<?php

namespace App\Livewire\Copies;

use App\Models\Copy;
use App\Models\Library;
use App\Models\Location;
use App\Models\Media;
use App\Models\User;
use App\Support\IsbnDisplayFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Create extends Component
{
    public int $mediaId;

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

    public function mount(int $mediaId): void
    {
        $media = Media::query()->findOrFail($mediaId);
        Gate::authorize('create', [Copy::class, $media]);

        $this->mediaId = $mediaId;

        $library = $this->libraryFor($media);
        $user = Auth::user();

        if (
            $user instanceof User
            && $library->users()->whereKey($user->getKey())->exists()
        ) {
            $this->ownerUserIds = [(int) $user->getKey()];
        } elseif ($library->owner_user_id !== null) {
            $this->ownerUserIds = [(int) $library->owner_user_id];
        }
    }

    public function save(): void
    {
        $media = Media::query()
            ->with('library')
            ->findOrFail($this->mediaId);

        Gate::authorize('create', [Copy::class, $media]);

        $library = $this->libraryFor($media);

        $validated = $this->validate([
            'locationId' => ['nullable', 'integer'],
            'inventoryCode' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'condition' => ['required', Rule::in(array_keys(Copy::conditions()))],
            'status' => ['required', Rule::in(array_keys(Copy::manuallyEditableStatuses()))],
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

        if ($locationId !== null && ! Location::query()
            ->whereKey($locationId)
            ->where('library_id', $media->library_id)
            ->exists()) {
            $this->addError('locationId', 'Der Standort gehört nicht zur aktiven Bibliothek.');

            return;
        }

        $ownerIds = collect($validated['ownerUserIds'])
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        $allowedOwnerIds = $library
            ->users()
            ->whereIn('users.id', $ownerIds->all())
            ->pluck('users.id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->sort()
            ->values();

        if ($allowedOwnerIds->all() !== $ownerIds->sort()->values()->all()) {
            $this->addError('ownerUserIds', 'Alle Eigentümer müssen Mitglieder der Bibliothek sein.');

            return;
        }

        $inventoryCode = $this->nullableString($validated['inventoryCode']);
        $barcode = IsbnDisplayFormatter::normalizeBarcode($validated['barcode']);

        if ($inventoryCode !== null && Copy::query()
            ->where('library_id', $media->library_id)
            ->where('inventory_code', $inventoryCode)
            ->exists()) {
            $this->addError('inventoryCode', 'Diese Inventarnummer ist bereits vergeben.');

            return;
        }

        if ($barcode !== null && Copy::query()
            ->where('library_id', $media->library_id)
            ->where('barcode', $barcode)
            ->exists()) {
            $this->addError('barcode', 'Dieser Barcode ist bereits vergeben.');

            return;
        }

        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        DB::transaction(function () use (
            $barcode,
            $inventoryCode,
            $locationId,
            $media,
            $ownerIds,
            $user,
            $validated,
        ): void {
            $copy = Copy::query()->create([
                'library_id' => $media->library_id,
                'media_id' => $media->getKey(),
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
                'created_by_user_id' => $user->getKey(),
                'updated_by_user_id' => $user->getKey(),
            ]);

            $copy->owners()->sync($ownerIds->all());
        });

        session()->flash('status', 'Exemplar wurde angelegt.');

        $this->redirectRoute('media.show', ['media' => $media->getKey()]);
    }

    public function formatBarcode(): void
    {
        $this->barcode = IsbnDisplayFormatter::format($this->barcode);
    }

    public function render(): View
    {
        $media = Media::query()
            ->with('library')
            ->findOrFail($this->mediaId);

        Gate::authorize('create', [Copy::class, $media]);

        $library = $this->libraryFor($media);

        return view('livewire.copies.create', [
            'media' => $media,
            'locations' => Location::query()
                ->where('library_id', $media->library_id)
                ->with('parent.parent.parent')
                ->orderBy('type')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'members' => $library
                ->users()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'conditions' => Copy::conditions(),
            'statuses' => Copy::manuallyEditableStatuses(),
        ]);
    }

    private function libraryFor(Media $media): Library
    {
        $library = $media->library;

        abort_unless($library instanceof Library, 404);

        return $library;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
