<form wire:submit="save" class="space-y-6">
    <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
        <p class="text-sm font-medium uppercase tracking-wide text-stone-500">{{ $library->name }}</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight">Medium anlegen</h1>

        @if ($duplicateMediaId !== null)
            <div class="mt-6 rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-950">
                <p class="font-medium">{{ $duplicateMessage }}</p>
                <p class="mt-1 text-sm">Prüfe den vorhandenen Datensatz, bevor du trotzdem speicherst.</p>

                <div class="mt-3 flex flex-wrap gap-3">
                    <a href="{{ route('media.show', $duplicateMediaId) }}" target="_blank" class="rounded-lg border border-amber-400 px-3 py-2 text-sm font-medium">
                        Treffer öffnen
                    </a>
                    <button type="button" wire:click="confirmDuplicateAndSave" class="rounded-lg bg-amber-900 px-3 py-2 text-sm font-medium text-white">
                        Trotzdem speichern
                    </button>
                    <button type="button" wire:click="cancelDuplicateWarning" class="rounded-lg px-3 py-2 text-sm font-medium">
                        Zurück
                    </button>
                </div>
            </div>
        @endif

        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            <label class="block">
                <span class="text-sm font-medium">Medientyp</span>
                <select wire:model="type" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('type') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <label class="block sm:col-span-2">
                <span class="text-sm font-medium">Titel</span>
                <input wire:model="title" type="text" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                @error('title') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <label class="block sm:col-span-2">
                <span class="text-sm font-medium">Untertitel</span>
                <input wire:model="subtitle" type="text" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                @error('subtitle') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <label class="block sm:col-span-2">
                <span class="text-sm font-medium">Sortiertitel</span>
                <input wire:model="sortTitle" type="text" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                @error('sortTitle') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <label class="block sm:col-span-2">
                <span class="text-sm font-medium">Urheber</span>
                <input wire:model="creators" type="text" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                @error('creators') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium">Verlag</span>
                <input wire:model="publisher" type="text" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                @error('publisher') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium">Erscheinungsort</span>
                <input wire:model="publicationPlace" type="text" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                @error('publicationPlace') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium">Erscheinungsjahr</span>
                <input wire:model="publicationYear" type="number" min="1000" max="2100" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                @error('publicationYear') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium">Ausgabe/Auflage</span>
                <input wire:model="edition" type="text" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                @error('edition') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium">Sprache</span>
                <input wire:model="languageCode" type="text" maxlength="8" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                @error('languageCode') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium">ISBN</span>
                <input wire:model="isbn" wire:blur="formatIsbn" type="text" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                @error('isbn') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium">ISSN</span>
                <input wire:model="issn" type="text" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                @error('issn') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <label class="block sm:col-span-2">
                <span class="text-sm font-medium">Cover-URL</span>
                <input wire:model="coverUrl" type="url" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                @error('coverUrl') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <label class="block sm:col-span-2">
                <span class="text-sm font-medium">Beschreibung</span>
                <textarea wire:model="description" rows="5" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2"></textarea>
                @error('description') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <button type="submit" class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-700">
                Speichern
            </button>
            <a href="{{ route('media.index') }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium hover:bg-stone-100">
                Abbrechen
            </a>
        </div>
    </section>
</form>
