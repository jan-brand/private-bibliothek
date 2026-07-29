<div class="space-y-6">
    <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <span class="rounded-full px-3 py-1 text-xs font-medium {{ $media->isPrivate() ? 'bg-amber-100 text-amber-900' : 'bg-emerald-100 text-emerald-900' }}">
                    {{ $media->isPrivate() ? 'Privates Medium' : 'Gemeinsames Medium' }}
                </span>

                @if ($media->isPrivate())
                    <span class="rounded-full bg-stone-100 px-3 py-1 text-xs text-stone-600">
                        Nur für {{ $media->owner?->name ?? 'den Eigentümer' }} und Administratoren
                    </span>
                @endif
            </div>

            @can('update', $media)
                <a href="{{ route('media.edit', $media) }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium hover:bg-stone-100">
                    Medium bearbeiten
                </a>
            @endcan
        </div>

        <div class="flex flex-col gap-6 sm:flex-row">
            @if ($coverUrl)
                <img src="{{ $coverUrl }}" alt="Cover von {{ $media->title }}" class="h-48 w-32 rounded-lg object-cover shadow-sm">
            @endif

            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium uppercase tracking-wide text-stone-500">
                    {{ $media->typeLabel() }} · {{ $media->library->name }}
                </p>

                <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $media->title }}</h1>

                @if ($media->subtitle)
                    <p class="mt-2 text-lg text-stone-600">{{ $media->subtitle }}</p>
                @endif

                @if ($media->creators)
                    <p class="mt-3 text-stone-600">{{ $media->creators }}</p>
                @endif

                <dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-stone-500">Katalog-Eigentümer</dt>
                        <dd class="mt-1">{{ $media->owner?->name ?? 'Unbekannt' }}</dd>
                    </div>

                    @if ($media->publisher)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-stone-500">Verlag</dt>
                            <dd class="mt-1">{{ $media->publisher }}</dd>
                        </div>
                    @endif

                    @if ($media->publication_place || $media->publication_year)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-stone-500">Erschienen</dt>
                            <dd class="mt-1">
                                {{ collect([$media->publication_place, $media->publication_year])->filter()->join(', ') }}
                            </dd>
                        </div>
                    @endif

                    @if ($media->edition)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-stone-500">Ausgabe</dt>
                            <dd class="mt-1">{{ $media->edition }}</dd>
                        </div>
                    @endif

                    @foreach ($media->identifiers as $identifier)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-stone-500">
                                {{ $identifier->label ?: strtoupper($identifier->scheme) }}
                            </dt>
                            <dd class="mt-1 font-mono text-sm">
                                {{ $identifier->scheme === 'isbn'
                                    ? \App\Support\IsbnDisplayFormatter::format($identifier->normalized_value)
                                    : $identifier->value }}
                            </dd>
                        </div>
                    @endforeach
                </dl>

                @if ($media->description)
                    <div class="mt-6 whitespace-pre-line text-sm leading-6 text-stone-600">{{ $media->description }}</div>
                @endif
            </div>
        </div>
    </section>

    <livewire:media.reading-status :media="$media" />

    @can('update', $media)
        <livewire:media.cover-manager :media="$media" />
    @endcan

    <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold">Exemplare</h2>

        <div class="mt-5 space-y-3">
            @forelse ($media->copies as $copy)
                <article class="rounded-xl border border-stone-200 bg-stone-50 p-4">
                    <div class="flex flex-col justify-between gap-3 sm:flex-row">
                        <div>
                            <p class="font-medium">
                                {{ $copy->inventory_code ?: 'Exemplar #'.$copy->id }}
                            </p>
                            <p class="mt-1 text-sm text-stone-500">
                                {{ $copy->conditionLabel() }} · {{ $copy->statusLabel() }}
                            </p>
                            @if ($copy->barcode)
                                <p class="mt-1 font-mono text-xs text-stone-500">
                                    {{ \App\Support\IsbnDisplayFormatter::format($copy->barcode) }}
                                </p>
                            @endif
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="text-sm text-stone-600">
                                {{ $copy->location?->breadcrumb() ?: 'Kein Standort' }}
                            </span>

                            @can('update', $copy)
                                <a href="{{ route('copies.edit', $copy) }}" class="rounded-lg border border-stone-300 px-3 py-1 text-sm font-medium">
                                    Bearbeiten
                                </a>
                            @endcan
                        </div>
                    </div>

                    <p class="mt-3 text-sm text-stone-600">
                        Eigentum: {{ $copy->owners->pluck('name')->join(', ') }}
                    </p>
                </article>
            @empty
                <p class="text-sm text-stone-500">Noch kein Exemplar vorhanden.</p>
            @endforelse
        </div>
    </section>

    @can('update', $media)
        <livewire:copies.create :media-id="$media->id" />
    @endcan
</div>
