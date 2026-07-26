<section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
    <h2 class="text-xl font-semibold">Cover verwalten</h2>

    <div class="mt-5 grid gap-6 lg:grid-cols-[12rem_1fr]">
        <div>
            @if ($coverUrl)
                <img src="{{ $coverUrl }}" alt="Cover von {{ $media->title }}" class="h-64 w-44 rounded-lg object-cover shadow-sm">
            @else
                <div class="flex h-64 w-44 items-center justify-center rounded-lg border border-dashed border-stone-300 bg-stone-50 p-4 text-center text-sm text-stone-500">
                    Kein Cover vorhanden
                </div>
            @endif
        </div>

        <div class="space-y-6">
            @if ($errorMessage !== '')
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                    {{ $errorMessage }}
                </div>
            @endif

            <form wire:submit="storeUpload" class="space-y-3">
                <div>
                    <label class="block text-sm font-medium">Bilddatei hochladen</label>
                    <input wire:model="upload" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="mt-2 block w-full text-sm">
                    <p class="mt-1 text-xs text-stone-500">JPEG, PNG, WebP oder GIF bis 5 MB.</p>
                    @error('upload') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
                </div>

                @if ($upload)
                    <img src="{{ $upload->temporaryUrl() }}" alt="Cover-Vorschau" class="h-40 w-28 rounded-lg object-cover">
                @endif

                <button type="submit" class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white">
                    Upload speichern
                </button>
            </form>

            <form wire:submit="importRemote" class="space-y-3">
                <label class="block">
                    <span class="text-sm font-medium">Cover von einer Bildadresse übernehmen</span>
                    <input wire:model="remoteUrl" type="url" placeholder="https://…" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                    @error('remoteUrl') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
                </label>

                <button type="submit" class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium">
                    Bild lokal speichern
                </button>
            </form>

            @if ($hasLocalCover)
                <button
                    type="button"
                    wire:click="removeLocal"
                    wire:confirm="Lokales Cover wirklich entfernen?"
                    class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-800"
                >
                    Lokales Cover entfernen
                </button>
            @endif
        </div>
    </div>
</section>
