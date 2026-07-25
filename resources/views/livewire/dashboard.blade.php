<div class="space-y-8">
    <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
        <p class="text-sm font-medium uppercase tracking-wide text-stone-500">
            Dashboard
        </p>

        <h1 class="mt-2 text-3xl font-semibold tracking-tight">
            Willkommen, {{ $user->name }}.
        </h1>

        <p class="mt-3 text-stone-600">
            Wähle die Bibliothek aus, in der du arbeiten möchtest.
        </p>
    </section>

    <livewire:library-switcher />

    <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Aktive Bibliothek</h2>

        @if ($selectedLibrary !== null)
            <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl bg-stone-100 p-4">
                    <dt class="text-sm text-stone-500">Name</dt>
                    <dd class="mt-1 font-medium">{{ $selectedLibrary->name }}</dd>
                </div>

                <div class="rounded-xl bg-stone-100 p-4">
                    <dt class="text-sm text-stone-500">Bereich</dt>
                    <dd class="mt-1 font-medium">
                        {{ $selectedLibrary->isPrivate() ? 'Private Bibliothek' : 'Gemeinsame Bibliothek' }}
                    </dd>
                </div>
            </dl>
        @else
            <p class="mt-3 text-sm text-stone-500">
                Keine zugängliche Bibliothek vorhanden.
            </p>
        @endif
    </section>
</div>
