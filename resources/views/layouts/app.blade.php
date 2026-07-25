<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        @hasSection('title')
            @yield('title') – {{ config('app.name') }}
        @else
            {{ config('app.name') }}
        @endif
    </title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="min-h-screen bg-stone-50 text-stone-950">
    <header class="border-b border-stone-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <a href="{{ route('home') }}" class="text-xl font-semibold tracking-tight">
                MiniBib
            </a>

            <nav class="flex items-center gap-3 text-sm">
                @auth
                    <a
                        href="{{ route('dashboard') }}"
                        class="rounded-lg px-3 py-2 font-medium text-stone-700 hover:bg-stone-100"
                    >
                        Dashboard
                    </a>

                    <span class="hidden text-stone-500 sm:inline">
                        {{ auth()->user()->name }}
                    </span>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button
                            type="submit"
                            class="rounded-lg border border-stone-300 px-3 py-2 font-medium text-stone-700 hover:bg-stone-100"
                        >
                            Abmelden
                        </button>
                    </form>
                @else
                    <a
                        href="{{ route('login') }}"
                        class="rounded-lg bg-stone-900 px-4 py-2 font-medium text-white hover:bg-stone-700"
                    >
                        Anmelden
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto min-h-[calc(100vh-145px)] max-w-6xl px-4 py-8 sm:px-6">
        @if (session('status'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="border-t border-stone-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-5 text-sm text-stone-500 sm:px-6">
            MiniBib · Private Bibliotheksverwaltung
        </div>
    </footer>

    @livewireScripts
</body>
</html>
