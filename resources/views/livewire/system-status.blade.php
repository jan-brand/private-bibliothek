<div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-lg font-semibold">Systemstatus</h2>
            <p class="mt-1 text-sm text-stone-500">
                Zuletzt geprüft: {{ $checkedAt }}
            </p>
        </div>

        <button
            type="button"
            wire:click="refreshStatus"
            wire:loading.attr="disabled"
            class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white disabled:cursor-wait disabled:opacity-50"
        >
            <span wire:loading.remove>Status aktualisieren</span>
            <span wire:loading>Prüfung läuft …</span>
        </button>
    </div>

    <dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl bg-stone-100 p-4">
            <dt class="text-sm text-stone-500">PHP</dt>
            <dd class="mt-1 font-medium">{{ $phpVersion }}</dd>
        </div>

        <div class="rounded-xl bg-stone-100 p-4">
            <dt class="text-sm text-stone-500">Laravel</dt>
            <dd class="mt-1 font-medium">{{ $laravelVersion }}</dd>
        </div>

        <div class="rounded-xl bg-stone-100 p-4">
            <dt class="text-sm text-stone-500">Datenbanktreiber</dt>
            <dd class="mt-1 font-medium">{{ $databaseConnection }}</dd>
        </div>

        <div class="rounded-xl bg-stone-100 p-4">
            <dt class="text-sm text-stone-500">Datenbank</dt>
            <dd class="mt-1 font-medium">
                @if ($databaseAvailable)
                    Bereit
                @else
                    Nicht erreichbar
                @endif
            </dd>
        </div>
    </dl>

    <p class="mt-4 text-xs text-stone-400">
        Aktualisierungen: {{ $refreshCount }}
    </p>
</div>
