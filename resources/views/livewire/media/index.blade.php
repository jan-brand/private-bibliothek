<div class="space-y-6">
    <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-stone-500">
                    {{ $library->name }}
                </p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">Medienkatalog</h1>
                <p class="mt-3 text-stone-600">
                    PostgreSQL-Volltextsuche über Katalogdaten, Kennungen und Exemplare.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('media.import') }}" class="rounded-lg border border-stone-300 px-4 py-2 text-center text-sm font-medium hover:bg-stone-100">
                    Metadaten importieren
                </a>
                <a href="{{ route('media.create') }}" class="rounded-lg bg-stone-900 px-4 py-2 text-center text-sm font-medium text-white hover:bg-stone-700">
                    Medium anlegen
                </a>
            </div>
        </div>

        <label class="mt-6 block">
            <span class="text-sm font-medium text-stone-700">Suche</span>
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Titel, Urheber, Beschreibung, ISBN, Barcode oder Inventarnummer"
                class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 outline-none focus:border-stone-700"
            >
        </label>

        <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <label class="block">
                <span class="text-sm font-medium text-stone-700">Medientyp</span>
                <select wire:model.live="type" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                    <option value="all">Alle Medientypen</option>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="text-sm font-medium text-stone-700">Sichtbarkeit</span>
                <select wire:model.live="visibility" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                    <option value="all">Alle sichtbaren Medien</option>
                    @foreach ($visibilities as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="text-sm font-medium text-stone-700">Bestand</span>
                <select wire:model.live="copyStatus" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                    @foreach ($copyStatuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="text-sm font-medium text-stone-700">Erscheinungsjahr von</span>
                <input
                    wire:model.live.debounce.300ms="yearFrom"
                    type="number"
                    min="1"
                    max="9999"
                    placeholder="z. B. 1990"
                    class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2"
                >
            </label>

            <label class="block">
                <span class="text-sm font-medium text-stone-700">Erscheinungsjahr bis</span>
                <input
                    wire:model.live.debounce.300ms="yearTo"
                    type="number"
                    min="1"
                    max="9999"
                    placeholder="z. B. 2026"
                    class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2"
                >
            </label>

            <label class="block">
                <span class="text-sm font-medium text-stone-700">Sortierung</span>
                <select wire:model.live="sort" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                    @foreach ($sortOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-stone-500">
                {{ $mediaItems->total() }}
                {{ $mediaItems->total() === 1 ? 'Treffer' : 'Treffer' }}
            </p>

            <button
                type="button"
                wire:click="clearFilters"
                class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium hover:bg-stone-100"
            >
                Suche und Filter zurücksetzen
            </button>
        </div>
    </section>

    <section class="space-y-3">
        @forelse ($mediaItems as $media)
            <a
                wire:key="media-{{ $media->id }}"
                href="{{ route('media.show', $media) }}"
                class="block rounded-2xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-stone-400"
            >
                <div class="flex flex-col justify-between gap-3 sm:flex-row">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-xs font-medium uppercase tracking-wide text-stone-500">
                                {{ $media->typeLabel() }}
                            </p>

                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $media->isPrivate() ? 'bg-amber-100 text-amber-900' : 'bg-emerald-100 text-emerald-900' }}">
                                {{ $media->visibilityLabel() }}
                            </span>

                            @if ($media->publication_year)
                                <span class="rounded-full bg-stone-100 px-2 py-0.5 text-xs text-stone-600">
                                    {{ $media->publication_year }}
                                </span>
                            @endif
                        </div>

                        <h2 class="mt-1 text-lg font-semibold">{{ $media->title }}</h2>

                        @if ($media->subtitle)
                            <p class="mt-1 text-sm text-stone-600">{{ $media->subtitle }}</p>
                        @endif

                        @if ($media->creators)
                            <p class="mt-2 text-sm text-stone-500">{{ $media->creators }}</p>
                        @endif
                    </div>

                    <div class="text-sm text-stone-500 sm:text-right">
                        <p>
                            {{ $media->copies_count }}
                            {{ $media->copies_count === 1 ? 'Exemplar' : 'Exemplare' }}
                        </p>
                        <p class="mt-1">
                            {{ $media->available_copies_count }} verfügbar
                        </p>
                    </div>
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-stone-300 bg-white p-8 text-center text-stone-500">
                Keine passenden Medien gefunden.
            </div>
        @endforelse
    </section>

    <div>
        {{ $mediaItems->links() }}
    </div>
</div>
