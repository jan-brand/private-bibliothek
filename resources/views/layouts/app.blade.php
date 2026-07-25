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
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
            <a href="{{ route('home') }}" class="text-xl font-semibold tracking-tight">
                MiniBib
            </a>

            <span class="text-sm text-stone-500">
                Private Bibliotheksverwaltung
            </span>
        </div>
    </header>

    <main class="mx-auto min-h-[calc(100vh-145px)] max-w-6xl px-4 py-8 sm:px-6">
        @yield('content')
    </main>

    <footer class="border-t border-stone-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-5 text-sm text-stone-500 sm:px-6">
            MiniBib · Projektgrundlage
        </div>
    </footer>

    @livewireScripts
</body>
</html>
