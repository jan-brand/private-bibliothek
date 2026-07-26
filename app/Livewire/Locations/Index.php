<?php

namespace App\Livewire\Locations;

use App\Models\Location;
use App\Models\User;
use App\Support\ResolvesCurrentLibrary;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Index extends Component
{
    use ResolvesCurrentLibrary;

    public ?int $editingId = null;

    public string $type = Location::TYPE_APARTMENT;

    public string $name = '';

    public string $parentId = '';

    public int $sortOrder = 0;

    public function edit(int $locationId): void
    {
        $library = $this->currentLibrary();
        $location = Location::query()
            ->whereKey($locationId)
            ->where('library_id', $library->getKey())
            ->firstOrFail();

        Gate::authorize('update', $location);

        $this->editingId = (int) $location->getKey();
        $this->type = $location->type;
        $this->name = $location->name;
        $this->parentId = $location->parent_id !== null
            ? (string) $location->parent_id
            : '';
        $this->sortOrder = $location->sort_order;
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        $library = $this->currentLibrary();

        $location = $this->editingId !== null
            ? Location::query()
                ->whereKey($this->editingId)
                ->where('library_id', $library->getKey())
                ->firstOrFail()
            : null;

        if ($location === null) {
            Gate::authorize('create', [Location::class, $library]);
        } else {
            Gate::authorize('update', $location);
        }

        $validated = $this->validate([
            'type' => ['required', Rule::in(array_keys(Location::types()))],
            'name' => ['required', 'string', 'max:255'],
            'parentId' => ['nullable', 'integer'],
            'sortOrder' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);

        if ($location !== null && $validated['type'] !== $location->type) {
            $this->addError(
                'type',
                'Die Ebene eines vorhandenen Standorts kann nicht geändert werden.',
            );

            return;
        }

        $requiredParentType = Location::requiredParentTypes()[$validated['type']] ?? null;
        $parent = null;

        if ($requiredParentType === null) {
            if ($validated['parentId'] !== '') {
                $this->addError(
                    'parentId',
                    'Eine Wohnung darf keinen übergeordneten Standort haben.',
                );

                return;
            }
        } else {
            if ($validated['parentId'] === '') {
                $this->addError(
                    'parentId',
                    'Ein übergeordneter Standort ist erforderlich.',
                );

                return;
            }

            $parent = Location::query()
                ->whereKey((int) $validated['parentId'])
                ->where('library_id', $library->getKey())
                ->where('type', $requiredParentType)
                ->first();

            if ($parent === null) {
                $this->addError(
                    'parentId',
                    'Der übergeordnete Standort hat nicht die erforderliche Ebene.',
                );

                return;
            }
        }

        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        if ($location === null) {
            Location::query()->create([
                'library_id' => $library->getKey(),
                'parent_id' => $parent?->getKey(),
                'type' => $validated['type'],
                'name' => trim($validated['name']),
                'sort_order' => $validated['sortOrder'],
                'created_by_user_id' => $user->getKey(),
            ]);

            session()->flash('status', 'Standort wurde angelegt.');
        } else {
            $location->update([
                'parent_id' => $parent?->getKey(),
                'name' => trim($validated['name']),
                'sort_order' => $validated['sortOrder'],
            ]);

            session()->flash('status', 'Standort wurde aktualisiert.');
        }

        $this->resetForm();
    }

    public function delete(int $locationId): void
    {
        $library = $this->currentLibrary();
        $location = Location::query()
            ->whereKey($locationId)
            ->where('library_id', $library->getKey())
            ->firstOrFail();

        Gate::authorize('delete', $location);

        if ($location->children()->exists()) {
            $this->addError(
                'delete',
                'Der Standort enthält noch untergeordnete Standorte.',
            );

            return;
        }

        if ($location->copies()->exists()) {
            $this->addError(
                'delete',
                'Dem Standort sind noch Exemplare zugeordnet.',
            );

            return;
        }

        $location->delete();
        $this->resetForm();

        session()->flash('status', 'Standort wurde gelöscht.');
    }

    public function render(): View
    {
        $library = $this->currentLibrary();
        Gate::authorize('viewAny', Location::class);

        $requiredParentType = Location::requiredParentTypes()[$this->type] ?? null;

        return view('livewire.locations.index', [
            'library' => $library,
            'types' => Location::types(),
            'requiredParentType' => $requiredParentType,
            'parentOptions' => $requiredParentType === null
                ? collect()
                : Location::query()
                    ->where('library_id', $library->getKey())
                    ->where('type', $requiredParentType)
                    ->with('parent.parent.parent')
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get(),
            'locations' => Location::query()
                ->where('library_id', $library->getKey())
                ->with('parent.parent.parent')
                ->withCount(['copies', 'children'])
                ->orderByRaw(
                    "case type when 'apartment' then 1 when 'room' then 2 when 'shelf' then 3 when 'board' then 4 else 5 end",
                )
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->type = Location::TYPE_APARTMENT;
        $this->name = '';
        $this->parentId = '';
        $this->sortOrder = 0;
        $this->resetValidation();
    }
}
