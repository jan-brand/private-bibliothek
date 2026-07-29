<section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
    <h2 class="text-xl font-semibold">Mein Lesestatus</h2>
    <p class="mt-2 text-sm text-stone-600">
        Dieser Status ist persönlich und für andere Benutzer nicht sichtbar.
    </p>

    <form wire:submit="save" class="mt-5 grid gap-4 lg:grid-cols-3">
        <label class="block">
            <span class="text-sm font-medium">Status</span>
            <select wire:model="status" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                <option value="">Kein Status</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('status') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-medium">Begonnen am</span>
            <input wire:model="startedAt" type="date" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
            @error('startedAt') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-medium">Beendet am</span>
            <input wire:model="finishedAt" type="date" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
            @error('finishedAt') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
        </label>

        <div class="lg:col-span-3">
            <button type="submit" class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white">
                Lesestatus speichern
            </button>
        </div>
    </form>
</section>
