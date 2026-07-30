<form wire:submit="save" class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
    <p class="text-sm font-medium uppercase tracking-wide text-stone-500">Ausleihen</p>
    <h1 class="mt-2 text-3xl font-semibold tracking-tight">Ausleihe erfassen</h1>

    <div class="mt-6 grid gap-5 sm:grid-cols-2">
        <label class="block sm:col-span-2">
            <span class="text-sm font-medium">Exemplar</span>
            <select wire:model="copyId" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                <option value="">Bitte auswählen</option>
                @foreach ($copies as $copy)
                    <option value="{{ $copy->id }}">
                        {{ $copy->inventory_code ?: 'Exemplar #'.$copy->id }}
                        – {{ $copy->media->title }}
                    </option>
                @endforeach
            </select>
            @error('copyId') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
        </label>

        <label class="block sm:col-span-2">
            <span class="text-sm font-medium">Entleihende Person</span>
            <select wire:model="borrowerId" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                <option value="">Bitte auswählen</option>
                @foreach ($borrowers as $borrower)
                    <option value="{{ $borrower->id }}">{{ $borrower->name }}</option>
                @endforeach
            </select>
            @error('borrowerId') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror

            <a href="{{ route('borrowers.index') }}" class="mt-2 inline-block text-sm font-medium underline">
                Neue Person anlegen
            </a>
        </label>

        <label class="block">
            <span class="text-sm font-medium">Ausgeliehen am</span>
            <input wire:model="loanedAt" type="date" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
            @error('loanedAt') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-medium">Fällig am</span>
            <input wire:model="dueAt" type="date" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
            @error('dueAt') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
        </label>

        <label class="block sm:col-span-2">
            <span class="text-sm font-medium">Notiz zur Ausleihe</span>
            <textarea wire:model="notes" rows="4" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2"></textarea>
            @error('notes') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
        </label>
    </div>

    <div class="mt-6 flex flex-wrap gap-3">
        <button type="submit" class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-700">
            Ausleihe speichern
        </button>

        <a href="{{ route('loans.index') }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium">
            Abbrechen
        </a>
    </div>
</form>
