<div class="space-y-6">
    <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-stone-500">Ausleihen</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">Ausleihverwaltung</h1>
                <p class="mt-3 text-stone-600">
                    Aktive, überfällige und zurückgegebene Ausleihen verwalten.
                </p>
            </div>

            <a href="{{ route('loans.create') }}" class="rounded-lg bg-stone-900 px-4 py-2 text-center text-sm font-medium text-white hover:bg-stone-700">
                Ausleihe erfassen
            </a>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <button type="button" wire:click="setFilter('active')" class="rounded-xl border p-4 text-left {{ $filter === 'active' ? 'border-stone-900 bg-stone-100' : 'border-stone-200' }}">
                <span class="text-sm text-stone-500">Aktiv</span>
                <span class="mt-1 block text-2xl font-semibold">{{ $activeCount }}</span>
            </button>

            <button type="button" wire:click="setFilter('overdue')" class="rounded-xl border p-4 text-left {{ $filter === 'overdue' ? 'border-red-800 bg-red-50' : 'border-stone-200' }}">
                <span class="text-sm text-stone-500">Überfällig</span>
                <span class="mt-1 block text-2xl font-semibold text-red-800">{{ $overdueCount }}</span>
            </button>

            <button type="button" wire:click="setFilter('returned')" class="rounded-xl border p-4 text-left {{ $filter === 'returned' ? 'border-stone-900 bg-stone-100' : 'border-stone-200' }}">
                <span class="text-sm text-stone-500">Zurückgegeben</span>
                <span class="mt-1 block text-2xl font-semibold">{{ $returnedCount }}</span>
            </button>
        </div>

        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
            <label class="block flex-1">
                <span class="text-sm font-medium">Suche</span>
                <input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="Person, Titel, Inventarnummer oder Barcode"
                    class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2"
                >
            </label>

            <button type="button" wire:click="setFilter('all')" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium {{ $filter === 'all' ? 'bg-stone-100' : '' }}">
                Gesamte Historie
            </button>
        </div>
    </section>

    <section class="space-y-4">
        @forelse ($loans as $loan)
            <article wire:key="loan-{{ $loan->id }}" class="rounded-2xl border bg-white p-5 shadow-sm {{ $loan->isOverdue() ? 'border-red-300' : 'border-stone-200' }}">
                <div class="flex flex-col justify-between gap-4 lg:flex-row">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-3 py-1 text-xs font-medium {{ $loan->isOverdue() ? 'bg-red-100 text-red-900' : ($loan->isActive() ? 'bg-amber-100 text-amber-900' : 'bg-emerald-100 text-emerald-900') }}">
                                {{ $loan->statusLabel() }}
                            </span>

                            <span class="text-sm text-stone-500">
                                Ausleihe #{{ $loan->id }}
                            </span>
                        </div>

                        <h2 class="mt-3 text-lg font-semibold">{{ $loan->copy->media->title }}</h2>
                        <p class="mt-1 text-sm text-stone-600">
                            {{ $loan->copy->inventory_code ?: 'Exemplar #'.$loan->copy->id }}
                            @if ($loan->copy->barcode)
                                · {{ \App\Support\IsbnDisplayFormatter::format($loan->copy->barcode) }}
                            @endif
                        </p>

                        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <dt class="text-stone-500">Entliehen von</dt>
                                <dd class="mt-1 font-medium">{{ $loan->borrower->name }}</dd>
                            </div>

                            <div>
                                <dt class="text-stone-500">Ausgeliehen am</dt>
                                <dd class="mt-1">{{ $loan->loaned_at->format('d.m.Y') }}</dd>
                            </div>

                            <div>
                                <dt class="text-stone-500">Fällig am</dt>
                                <dd class="mt-1 {{ $loan->isOverdue() ? 'font-semibold text-red-800' : '' }}">
                                    {{ $loan->due_at?->format('d.m.Y') ?? 'Ohne Frist' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-stone-500">Erfasst von</dt>
                                <dd class="mt-1">{{ $loan->loanedBy->name }}</dd>
                            </div>
                        </dl>

                        @if ($loan->notes)
                            <p class="mt-4 whitespace-pre-line rounded-lg bg-stone-50 p-3 text-sm text-stone-600">{{ $loan->notes }}</p>
                        @endif

                        @if ($loan->returned_at)
                            <p class="mt-4 text-sm text-stone-600">
                                Zurückgegeben am {{ $loan->returned_at->format('d.m.Y') }}
                                @if ($loan->returnedBy)
                                    von {{ $loan->returnedBy->name }}
                                @endif
                            </p>

                            @if ($loan->return_notes)
                                <p class="mt-2 whitespace-pre-line text-sm text-stone-600">{{ $loan->return_notes }}</p>
                            @endif
                        @endif
                    </div>

                    @if ($loan->isActive())
                        <div class="w-full lg:w-80">
                            <label class="block">
                                <span class="text-sm font-medium">Rückgabenotiz</span>
                                <textarea wire:model="returnNotes.{{ $loan->id }}" rows="3" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2"></textarea>
                                @error('returnNotes.'.$loan->id) <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
                            </label>

                            <button
                                type="button"
                                wire:click="returnLoan({{ $loan->id }})"
                                wire:confirm="Rückgabe dieses Exemplars erfassen?"
                                class="mt-3 w-full rounded-lg bg-emerald-800 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                            >
                                Rückgabe erfassen
                            </button>
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-stone-300 bg-white p-8 text-center text-stone-500">
                Keine passenden Ausleihen vorhanden.
            </div>
        @endforelse
    </section>

    <div>
        {{ $loans->links() }}
    </div>
</div>
