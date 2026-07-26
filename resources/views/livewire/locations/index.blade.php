<div class="space-y-6">
    <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
        <p class="text-sm font-medium uppercase tracking-wide text-stone-500">{{ $library->name }}</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight">Standorte</h1>
        <p class="mt-3 text-stone-600">Hierarchie: Wohnung → Raum → Regal → Regalbrett.</p>
    </section>

    <form wire:submit="save" class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold">
            {{ $editingId === null ? 'Standort anlegen' : 'Standort bearbeiten' }}
        </h2>

        <div class="mt-5 grid gap-5 sm:grid-cols-2">
            <label class="block">
                <span class="text-sm font-medium">Ebene</span>
                <select wire:model="type" @disabled($editingId !== null) class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 disabled:bg-stone-100">
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('type') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium">Name</span>
                <input wire:model="name" type="text" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                @error('name') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            @if ($requiredParentType !== null)
                <label class="block sm:col-span-2">
                    <span class="text-sm font-medium">Übergeordneter Standort</span>
                    <select wire:model="parentId" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                        <option value="">Bitte auswählen</option>
                        @foreach ($parentOptions as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->breadcrumb() }}</option>
                        @endforeach
                    </select>
                    @error('parentId') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
                </label>
            @endif

            <label class="block">
                <span class="text-sm font-medium">Sortierung</span>
                <input wire:model="sortOrder" type="number" min="0" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                @error('sortOrder') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white">
                {{ $editingId === null ? 'Standort speichern' : 'Änderungen speichern' }}
            </button>

            @if ($editingId !== null)
                <button type="button" wire:click="cancelEdit" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium">
                    Abbrechen
                </button>
            @endif
        </div>

        @error('delete') <p class="mt-3 text-sm text-red-700">{{ $message }}</p> @enderror
    </form>

    <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold">Vorhandene Standorte</h2>

        <div class="mt-5 space-y-3">
            @forelse ($locations as $location)
                <article wire:key="location-{{ $location->id }}" class="rounded-xl border border-stone-200 bg-stone-50 p-4">
                    <div class="flex flex-col justify-between gap-3 sm:flex-row">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-stone-500">{{ $location->typeLabel() }}</p>
                            <p class="mt-1 font-medium">{{ $location->breadcrumb() }}</p>
                            <p class="mt-1 text-sm text-stone-500">
                                {{ $location->copies_count }} Exemplare ·
                                {{ $location->children_count }} Unterstandorte
                            </p>
                        </div>

                        <div class="flex items-start gap-2">
                            <button type="button" wire:click="edit({{ $location->id }})" class="rounded-lg border border-stone-300 px-3 py-2 text-sm font-medium">
                                Bearbeiten
                            </button>
                            <button type="button" wire:click="delete({{ $location->id }})" wire:confirm="Standort wirklich löschen?" class="rounded-lg border border-red-300 px-3 py-2 text-sm font-medium text-red-800">
                                Löschen
                            </button>
                        </div>
                    </div>
                </article>
            @empty
                <p class="text-sm text-stone-500">Noch keine Standorte vorhanden.</p>
            @endforelse
        </div>
    </section>
</div>
