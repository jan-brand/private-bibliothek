@extends('layouts.app')

@section('title', 'Start')

@section('content')
    <div class="space-y-8">
        <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
            <p class="mb-2 text-sm font-medium uppercase tracking-wide text-stone-500">
                Projektgrundlage
            </p>

            <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">
                MiniBib ist bereit.
            </h1>

            <p class="mt-4 max-w-2xl text-base leading-7 text-stone-600">
                Laravel, PostgreSQL und Livewire bilden die technische
                Grundlage für die private Bibliotheksverwaltung.
            </p>
        </section>

        <livewire:system-status />
    </div>
@endsection
