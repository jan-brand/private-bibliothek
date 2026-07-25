<div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
    <div>
        <h2 class="text-lg font-semibold">Bibliothek auswählen</h2>
        <p class="mt-1 text-sm text-stone-500">
            Private Bibliotheken sind nur für ihren Eigentümer sichtbar. Die gemeinsame Bibliothek ist für Mitglieder sichtbar.
        </p>
    </div>

    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($libraries as $library)
            <button
                type="button"
                wire:key="library-{{ $library->id }}"
                wire:click="selectLibrary({{ $library->id }})"
                class="rounded-xl border p-4 text-left transition {{ $selectedLibraryId === $library->id ? 'border-stone-900 bg-stone-900 text-white' : 'border-stone-200 bg-stone-50 hover:border-stone-400' }}"
            >
                <span class="block font-medium">{{ $library->name }}</span>
                <span class="mt-1 block text-xs {{ $selectedLibraryId === $library->id ? 'text-stone-300' : 'text-stone-500' }}">
                    {{ $library->isPrivate() ? 'Privat' : 'Gemeinsam' }}
                </span>
            </button>
        @empty
            <p class="text-sm text-stone-500">
                Diesem Benutzer ist noch keine Bibliothek zugeordnet.
            </p>
        @endforelse
    </div>
</div>
