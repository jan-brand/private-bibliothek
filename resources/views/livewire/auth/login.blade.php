<form wire:submit="login" class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
    <div>
        <p class="text-sm font-medium uppercase tracking-wide text-stone-500">
            Geschützter Bereich
        </p>

        <h1 class="mt-2 text-3xl font-semibold tracking-tight">
            Bei MiniBib anmelden
        </h1>

        <p class="mt-3 text-sm leading-6 text-stone-600">
            Es gibt keine öffentliche Registrierung. Benutzerkonten werden administrativ angelegt.
        </p>
    </div>

    <div class="mt-8 space-y-5">
        <div>
            <label for="email" class="block text-sm font-medium text-stone-700">
                E-Mail-Adresse
            </label>

            <input
                id="email"
                type="email"
                wire:model="email"
                autocomplete="username"
                required
                autofocus
                class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-stone-950 shadow-sm outline-none focus:border-stone-900 focus:ring-2 focus:ring-stone-200"
            >

            @error('email')
                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-stone-700">
                Passwort
            </label>

            <input
                id="password"
                type="password"
                wire:model="password"
                autocomplete="current-password"
                required
                class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-stone-950 shadow-sm outline-none focus:border-stone-900 focus:ring-2 focus:ring-stone-200"
            >

            @error('password')
                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-3 text-sm text-stone-700">
            <input
                type="checkbox"
                wire:model="remember"
                class="h-4 w-4 rounded border-stone-300"
            >
            Angemeldet bleiben
        </label>
    </div>

    <button
        type="submit"
        wire:loading.attr="disabled"
        class="mt-7 w-full rounded-lg bg-stone-900 px-4 py-3 text-sm font-semibold text-white hover:bg-stone-700 disabled:cursor-wait disabled:opacity-60"
    >
        <span wire:loading.remove>Anmelden</span>
        <span wire:loading>Anmeldung läuft …</span>
    </button>
</form>
