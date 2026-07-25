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

    public string $type = Location::TYPE_APARTMENT;

    public string $name = '';

    public string $parentId = '';

    public int $sortOrder = 0;

    public function updatedType(): void
    {
        $this->parentId = '';
        $this->resetValidation();
    }

    public function save(): void
    {
        $library = $this->currentLibrary();
        Gate::authorize('create', [Location::class, $library]);

        $validated = $this->validate([
            'type' => ['required', Rule::in(array_keys(Location::types()))],
            'name' => ['required', 'string', 'max:255'],
            'parentId' => ['nullable', 'integer'],
            'sortOrder' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);

        $requiredParentType = Location::requiredParentTypes()[$validated['type']] ?? null;
        $parent = null;

        if ($requiredParentType === null) {
            if ($validated['parentId'] !== '') {
                $this->addError('parentId', 'Eine Wohnung darf keinen übergeordneten Standort haben.');

                return;
            }
        } else {
            if ($validated['parentId'] === '') {
                $this->addError('parentId', 'Für diesen Standort ist ein übergeordneter Standort erforderlich.');

                return;
            }

            $parent = Location::query()
                ->whereKey((int) $validated['parentId'])
                ->where('library_id', $library->getKey())
                ->where('type', $requiredParentType)
                ->first();

            if ($parent === null) {
                $this->addError('parentId', 'Der übergeordnete Standort hat nicht die erforderliche Ebene.');

                return;
            }
        }

        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        Location::query()->create([
            'library_id' => $library->getKey(),
            'parent_id' => $parent?->getKey(),
            'type' => $validated['type'],
            'name' => trim($validated['name']),
            'sort_order' => $validated['sortOrder'],
            'created_by_user_id' => $user->getKey(),
        ]);

        $this->reset('name', 'parentId', 'sortOrder');
        $this->sortOrder = 0;

        session()->flash('status', 'Standort wurde angelegt.');
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
                ->withCount('copies')
                ->orderByRaw(
                    "case type when 'apartment' then 1 when 'room' then 2 when 'shelf' then 3 when 'board' then 4 else 5 end",
                )
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
