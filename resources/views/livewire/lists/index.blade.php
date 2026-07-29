<div class="space-y-6">
    <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
        <p class="text-sm font-medium uppercase tracking-wide text-stone-500">{{ $library->name }}</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight">Listen</h1>
        <p class="mt-3 text-stone-600">
            Private Listen sind nur für dich sichtbar. Gemeinsame Listen sehen alle Mitglieder dieser Bibliothek.
        </p>

        <form wire:submit="createList" class="mt-6 grid gap-4 lg:grid-cols-2">
            <label class="block">
                <span class="text-sm font-medium">Name</span>
                <input wire:model="name" type="text" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                @error('name') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium">Sichtbarkeit</span>
                <select wire:model="visibility" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                    @foreach ($visibilities as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('visibility') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <label class="block lg:col-span-2">
                <span class="text-sm font-medium">Beschreibung</span>
                <textarea wire:model="description" rows="3" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2"></textarea>
                @error('description') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <div class="lg:col-span-2">
                <button type="submit" class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white">
                    Liste anlegen
                </button>
            </div>
        </form>
    </section>

    <section class="grid gap-4 md:grid-cols-2">
        @forelse ($lists as $mediaList)
            <article class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-stone-500">
                            {{ $mediaList->visibilityLabel() }} · {{ $mediaList->items_count }}
                            {{ $mediaList->items_count === 1 ? 'Medium' : 'Medien' }}
                        </p>
                        <h2 class="mt-1 text-xl font-semibold">
                            <a href="{{ route('lists.show', $mediaList) }}" class="hover:underline">
                                {{ $mediaList->name }}
                            </a>
                        </h2>

                        @if ($mediaList->description)
                            <p class="mt-2 text-sm text-stone-600">{{ $mediaList->description }}</p>
                        @endif

                        <p class="mt-3 text-xs text-stone-500">
                            Eigentümer: {{ $mediaList->owner->name }}
                        </p>
                    </div>

                    @can('delete', $mediaList)
                        <button
                            type="button"
                            wire:click="deleteList({{ $mediaList->id }})"
                            wire:confirm="Liste wirklich löschen?"
                            class="rounded-lg border border-red-300 px-3 py-1 text-sm font-medium text-red-800"
                        >
                            Löschen
                        </button>
                    @endcan
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-stone-300 bg-white p-8 text-center text-stone-500 md:col-span-2">
                Noch keine sichtbaren Listen vorhanden.
            </div>
        @endforelse
    </section>
</div>
