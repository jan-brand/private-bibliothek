<form wire:submit="save" class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
    <h1 class="text-3xl font-semibold tracking-tight">Exemplar bearbeiten</h1>
    <p class="mt-2 text-stone-600">{{ $copy->media->title }}</p>

    @if ($hasActiveLoan)
        <div class="mt-5 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
            Dieses Exemplar ist aktiv ausgeliehen. Der Status wird durch die Ausleihverwaltung gesteuert.
            <a href="{{ route('loans.index') }}" class="ml-1 font-medium underline">Ausleihe öffnen</a>
        </div>
    @endif

    <div class="mt-6 grid gap-5 sm:grid-cols-2">
        <label class="block">
            <span class="text-sm font-medium">Inventarnummer</span>
            <input wire:model="inventoryCode" type="text" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
            @error('inventoryCode') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-medium">Barcode</span>
            <input wire:model="barcode" wire:blur="formatBarcode" type="text" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
            @error('barcode') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
        </label>

        <label class="block sm:col-span-2">
            <span class="text-sm font-medium">Standort</span>
            <select wire:model="locationId" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                <option value="">Kein Standort</option>
                @foreach ($locations as $location)
                    <option value="{{ $location->id }}">{{ $location->breadcrumb() }}</option>
                @endforeach
            </select>
            @error('locationId') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-medium">Zustand</span>
            <select wire:model="condition" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                @foreach ($conditions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('condition') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-medium">Status</span>
            <select wire:model="status" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2" @disabled($hasActiveLoan)>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('status') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
        </label>

        <fieldset class="sm:col-span-2">
            <legend class="text-sm font-medium">Eigentümer</legend>
            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                @foreach ($members as $member)
                    <label class="flex items-center gap-2 rounded-lg border border-stone-200 p-3">
                        <input wire:model="ownerUserIds" type="checkbox" value="{{ $member->id }}">
                        <span>{{ $member->name }}</span>
                    </label>
                @endforeach
            </div>
            @error('ownerUserIds') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
        </fieldset>

        <label class="block">
            <span class="text-sm font-medium">Erworben am</span>
            <input wire:model="acquiredAt" type="date" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
        </label>

        <label class="block">
            <span class="text-sm font-medium">Bezugsquelle</span>
            <input wire:model="acquisitionSource" type="text" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
        </label>

        <label class="block">
            <span class="text-sm font-medium">Kaufpreis</span>
            <input wire:model="purchasePrice" type="number" min="0" step="0.01" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
        </label>

        <label class="block sm:col-span-2">
            <span class="text-sm font-medium">Notiz</span>
            <textarea wire:model="notes" rows="3" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2"></textarea>
        </label>
    </div>

    <div class="mt-6 flex flex-wrap justify-between gap-3">
        <div class="flex gap-3">
            <button type="submit" class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white">
                Änderungen speichern
            </button>
            <a href="{{ route('media.show', $copy->media_id) }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium">
                Abbrechen
            </a>
        </div>

        <button type="button" wire:click="delete" wire:confirm="Exemplar wirklich löschen?" class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-800">
            Exemplar löschen
        </button>
    </div>

    @error('delete') <p class="mt-3 text-sm text-red-700">{{ $message }}</p> @enderror
</form>
