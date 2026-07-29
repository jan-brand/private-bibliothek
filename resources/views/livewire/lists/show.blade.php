<div class="space-y-6">
    <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-stone-500">
                    {{ $mediaList->visibilityLabel() }}
                </p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $mediaList->name }}</h1>

                @if ($mediaList->description)
                    <p class="mt-3 text-stone-600">{{ $mediaList->description }}</p>
                @endif
            </div>

            <a href="{{ route('lists.index') }}" class="self-start rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium">
                Zurück zu Listen
            </a>
        </div>
    </section>

    @can('update', $mediaList)
        <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-semibold">Liste bearbeiten</h2>

            <form wire:submit="saveList" class="mt-5 grid gap-4 lg:grid-cols-2">
                <label class="block">
                    <span class="text-sm font-medium">Name</span>
                    <input wire:model="name" type="text" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                    @error('name') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium">Sichtbarkeit</span>
                    <select wire:model="visibility" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                        @foreach ($visibilities as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <span class="mt-1 block text-xs text-stone-500">
                        Gemeinsame Listen dürfen nur gemeinsame Medien enthalten.
                    </span>
                    @error('visibility') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block lg:col-span-2">
                    <span class="text-sm font-medium">Beschreibung</span>
                    <textarea wire:model="description" rows="3" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2"></textarea>
                    @error('description') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
                </label>

                <div class="flex flex-wrap gap-3 lg:col-span-2">
                    <button type="submit" class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white">
                        Änderungen speichern
                    </button>

                    <button
                        type="button"
                        wire:click="deleteList"
                        wire:confirm="Liste wirklich löschen?"
                        class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-800"
                    >
                        Liste löschen
                    </button>
                </div>
            </form>
        </section>

        <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-semibold">Medium hinzufügen</h2>

            <form wire:submit="addMedia" class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
                <label class="block flex-1">
                    <span class="text-sm font-medium">Medium</span>
                    <select wire:model="mediaId" class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2">
                        <option value="">Bitte auswählen</option>
                        @foreach ($availableMedia as $media)
                            <option value="{{ $media->id }}">
                                {{ $media->title }}{{ $media->isPrivate() ? ' · Privat' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('mediaId') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
                </label>

                <button type="submit" class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white">
                    Hinzufügen
                </button>
            </form>
        </section>
    @endcan

    <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold">Medien</h2>

        <div class="mt-5 space-y-3">
            @forelse ($items as $item)
                <article class="rounded-xl border border-stone-200 bg-stone-50 p-4">
                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-xs font-medium uppercase tracking-wide text-stone-500">
                                    Position {{ $item->position }}
                                </p>

                                @if ($item->media->isPrivate())
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-900">
                                        Privat
                                    </span>
                                @endif
                            </div>

                            <h3 class="mt-1 font-semibold">
                                <a href="{{ route('media.show', $item->media) }}" class="hover:underline">
                                    {{ $item->media->title }}
                                </a>
                            </h3>

                            @if ($item->media->creators)
                                <p class="mt-1 text-sm text-stone-600">{{ $item->media->creators }}</p>
                            @endif
                        </div>

                        @can('update', $mediaList)
                            <div class="flex flex-wrap gap-2">
                                <button type="button" wire:click="moveItem({{ $item->id }}, 'up')" class="rounded-lg border border-stone-300 px-3 py-1 text-sm">
                                    Hoch
                                </button>
                                <button type="button" wire:click="moveItem({{ $item->id }}, 'down')" class="rounded-lg border border-stone-300 px-3 py-1 text-sm">
                                    Runter
                                </button>
                                <button type="button" wire:click="removeItem({{ $item->id }})" class="rounded-lg border border-red-300 px-3 py-1 text-sm text-red-800">
                                    Entfernen
                                </button>
                            </div>
                        @endcan
                    </div>
                </article>
            @empty
                <p class="text-sm text-stone-500">Diese Liste enthält noch keine Medien.</p>
            @endforelse
        </div>
    </section>
</div>
