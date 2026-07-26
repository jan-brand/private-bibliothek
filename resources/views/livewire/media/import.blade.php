<div class="space-y-6">
    <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
        <p class="text-sm font-medium uppercase tracking-wide text-stone-500">{{ $library->name }}</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight">Metadaten importieren</h1>
        <p class="mt-3 text-stone-600">
            Suche Bücher über ISBN in der DNB oder fortlaufende Publikationen über ISSN beziehungsweise ZDB-ID in der ZDB.
        </p>

        <form wire:submit="search" class="mt-6 grid gap-5 sm:grid-cols-[12rem_1fr_auto] sm:items-end">
            <label class="block">
                <span class="text-sm font-medium">Datenquelle</span>
                <select wire:model="source" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                    <option value="auto">Automatisch</option>
                    <option value="dnb">DNB</option>
                    <option value="zdb">ZDB</option>
                </select>
                @error('source') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium">ISBN, ISSN oder ZDB-ID</span>
                <input wire:model="identifier" type="text" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                @error('identifier') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <button type="submit" class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-700">
                Suchen
            </button>
        </form>

        @if ($errorMessage !== '')
            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                {{ $errorMessage }}
            </div>
        @endif
    </section>

    @if ($result !== null)
        <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-col justify-between gap-4 sm:flex-row">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-stone-500">
                        Treffer aus {{ strtoupper((string) $result['source']) }}
                    </p>
                    <h2 class="mt-2 text-2xl font-semibold">{{ $result['title'] }}</h2>

                    @if ($result['subtitle'])
                        <p class="mt-2 text-stone-600">{{ $result['subtitle'] }}</p>
                    @endif
                </div>

                @if ($result['source_record_id'])
                    <p class="font-mono text-sm text-stone-500">{{ $result['source_record_id'] }}</p>
                @endif
            </div>

            <dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @if ($result['creators'])
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-stone-500">Urheber</dt>
                        <dd class="mt-1">{{ implode('; ', $result['creators']) }}</dd>
                    </div>
                @endif

                @if ($result['publisher'])
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-stone-500">Verlag</dt>
                        <dd class="mt-1">{{ $result['publisher'] }}</dd>
                    </div>
                @endif

                @if ($result['publication_place'] || $result['publication_year'])
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-stone-500">Erschienen</dt>
                        <dd class="mt-1">
                            {{ collect([$result['publication_place'], $result['publication_year']])->filter()->join(', ') }}
                        </dd>
                    </div>
                @endif

                @foreach ($result['identifiers'] as $scheme => $value)
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-stone-500">{{ strtoupper($scheme) }}</dt>
                        <dd class="mt-1 font-mono text-sm">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>

            @if ($result['description'])
                <p class="mt-6 whitespace-pre-line text-sm leading-6 text-stone-600">{{ $result['description'] }}</p>
            @endif

            <div class="mt-6 flex flex-wrap gap-3">
                <button wire:click="apply" type="button" class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white">
                    In Medienformular übernehmen
                </button>
                <button wire:click="clearResult" type="button" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium">
                    Treffer verwerfen
                </button>
            </div>
        </section>
    @endif
</div>
