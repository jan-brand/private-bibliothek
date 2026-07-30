<div class="space-y-6">
    <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
        <p class="text-sm font-medium uppercase tracking-wide text-stone-500">Ausleihen</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight">Entleihende Personen</h1>
        <p class="mt-3 text-stone-600">
            Kontaktdaten für Personen verwalten, an die Exemplare ausgeliehen werden.
        </p>
    </section>

    <form wire:submit="save" class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold">
            {{ $editingBorrowerId === null ? 'Person anlegen' : 'Person bearbeiten' }}
        </h2>

        <div class="mt-5 grid gap-5 sm:grid-cols-2">
            <label class="block sm:col-span-2">
                <span class="text-sm font-medium">Name</span>
                <input wire:model="name" type="text" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                @error('name') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium">E-Mail</span>
                <input wire:model="email" type="email" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                @error('email') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium">Telefon</span>
                <input wire:model="phone" type="text" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                @error('phone') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <label class="block sm:col-span-2">
                <span class="text-sm font-medium">Notizen</span>
                <textarea wire:model="notes" rows="3" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2"></textarea>
                @error('notes') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <button type="submit" class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-700">
                {{ $editingBorrowerId === null ? 'Person speichern' : 'Änderungen speichern' }}
            </button>

            @if ($editingBorrowerId !== null)
                <button type="button" wire:click="cancelEdit" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium">
                    Abbrechen
                </button>
            @endif
        </div>
    </form>

    <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h2 class="text-xl font-semibold">Gespeicherte Personen</h2>
                <p class="mt-1 text-sm text-stone-500">
                    Personen mit Ausleihhistorie bleiben aus Nachweisgründen erhalten.
                </p>
            </div>

            <label class="block sm:w-80">
                <span class="text-sm font-medium">Suche</span>
                <input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="Name, E-Mail oder Telefon"
                    class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2"
                >
            </label>
        </div>

        @error('delete') <p class="mt-4 text-sm text-red-700">{{ $message }}</p> @enderror

        <div class="mt-5 space-y-3">
            @forelse ($borrowers as $borrower)
                <article wire:key="borrower-{{ $borrower->id }}" class="rounded-xl border border-stone-200 bg-stone-50 p-4">
                    <div class="flex flex-col justify-between gap-4 sm:flex-row">
                        <div>
                            <h3 class="font-semibold">{{ $borrower->name }}</h3>

                            @if ($borrower->email)
                                <p class="mt-1 text-sm text-stone-600">{{ $borrower->email }}</p>
                            @endif

                            @if ($borrower->phone)
                                <p class="mt-1 text-sm text-stone-600">{{ $borrower->phone }}</p>
                            @endif

                            <p class="mt-2 text-sm text-stone-500">
                                {{ $borrower->active_loans_count }} aktiv ·
                                {{ $borrower->loans_count }} insgesamt
                            </p>

                            @if ($borrower->notes)
                                <p class="mt-3 whitespace-pre-line text-sm text-stone-600">{{ $borrower->notes }}</p>
                            @endif
                        </div>

                        <div class="flex items-start gap-2">
                            <button
                                type="button"
                                wire:click="edit({{ $borrower->id }})"
                                class="rounded-lg border border-stone-300 px-3 py-2 text-sm font-medium hover:bg-white"
                            >
                                Bearbeiten
                            </button>

                            <button
                                type="button"
                                wire:click="delete({{ $borrower->id }})"
                                wire:confirm="Entleihende Person wirklich löschen?"
                                class="rounded-lg border border-red-300 px-3 py-2 text-sm font-medium text-red-800 hover:bg-red-50"
                            >
                                Löschen
                            </button>
                        </div>
                    </div>
                </article>
            @empty
                <p class="text-sm text-stone-500">Noch keine entleihenden Personen vorhanden.</p>
            @endforelse
        </div>
    </section>
</div>
