<div class="space-y-8">
    <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
        <p class="text-sm font-medium uppercase tracking-wide text-stone-500">
            Dashboard
        </p>

        <h1 class="mt-2 text-3xl font-semibold tracking-tight">
            Willkommen, {{ $user->name }}.
        </h1>

        <p class="mt-3 text-stone-600">
            Alle Benutzer arbeiten gemeinsam im Katalog {{ $library->name }}.
            Private Medien und Listen bleiben nur für ihre Eigentümer sichtbar.
        </p>
    </section>

    <section class="grid gap-4 sm:grid-cols-2">
        <article class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <p class="text-sm text-stone-500">Für dich sichtbare Medien</p>
            <p class="mt-2 text-3xl font-semibold">{{ $visibleMediaCount }}</p>
        </article>

        <article class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <p class="text-sm text-stone-500">Deine privaten Medien</p>
            <p class="mt-2 text-3xl font-semibold">{{ $privateMediaCount }}</p>
        </article>
    </section>

    <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">MiniBib öffnen</h2>

        <div class="mt-5 flex flex-wrap gap-3">
            <a href="{{ route('media.index') }}" class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-700">
                Medien öffnen
            </a>

            <a href="{{ route('lists.index') }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium hover:bg-stone-100">
                Listen öffnen
            </a>

            <a href="{{ route('locations.index') }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium hover:bg-stone-100">
                Standorte verwalten
            </a>
        </div>
    </section>
</div>
