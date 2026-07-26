<div class="space-y-6">
    <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-stone-500">
                    {{ $library->name }}
                </p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">Medienkatalog</h1>
                <p class="mt-3 text-stone-600">
                    Bibliografische Datensätze und ihre vorhandenen Exemplare.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route('media.import') }}"
                    class="rounded-lg border border-stone-300 px-4 py-2 text-center text-sm font-medium hover:bg-stone-100"
                >
                    Metadaten importieren
                </a>
                <a
                    href="{{ route('media.create') }}"
                    class="rounded-lg bg-stone-900 px-4 py-2 text-center text-sm font-medium text-white hover:bg-stone-700"
                >
                    Medium anlegen
                </a>
            </div>
        </div>

        <label class="mt-6 block">
            <span class="text-sm font-medium text-stone-700">Suche</span>
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Titel, Urheber, Verlag oder Kennung"
                class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 outline-none focus:border-stone-700"
            >
        </label>
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
                        <p class="text-xs font-medium uppercase tracking-wide text-stone-500">
                            {{ $media->typeLabel() }}
                        </p>
                        <h2 class="mt-1 text-lg font-semibold">{{ $media->title }}</h2>

                        @if ($media->subtitle)
                            <p class="mt-1 text-sm text-stone-600">{{ $media->subtitle }}</p>
                        @endif

                        @if ($media->creators)
                            <p class="mt-2 text-sm text-stone-500">{{ $media->creators }}</p>
                        @endif
                    </div>

                    <div class="text-sm text-stone-500">
                        {{ $media->copies_count }}
                        {{ $media->copies_count === 1 ? 'Exemplar' : 'Exemplare' }}
                    </div>
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-stone-300 bg-white p-8 text-center text-stone-500">
                Noch keine passenden Medien vorhanden.
            </div>
        @endforelse
    </section>

    <div>
        {{ $mediaItems->links() }}
    </div>
</div>
