<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\MediaCoverController;
use App\Models\Copy;
use App\Models\Media;
use App\Models\MediaList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'auth.login')->name('login');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::view('/media', 'media.index')->name('media.index');
    Route::view('/media/import', 'media.import')
        ->name('media.import');
    Route::view('/media/create', 'media.create')
        ->name('media.create');

    Route::get(
        '/media/{media}/cover',
        MediaCoverController::class,
    )->name('media.cover');

    Route::get('/media/{media}/edit', function (Media $media) {
        Gate::authorize('update', $media);

        return view('media.edit', [
            'media' => $media,
        ]);
    })->name('media.edit');

    Route::get('/media/{media}', function (Media $media) {
        Gate::authorize('view', $media);

        return view('media.show', [
            'media' => $media,
        ]);
    })->name('media.show');

    Route::view('/lists', 'lists.index')
        ->name('lists.index');

    Route::get(
        '/lists/{mediaList}',
        function (MediaList $mediaList) {
            Gate::authorize('view', $mediaList);

            return view('lists.show', [
                'mediaList' => $mediaList,
            ]);
        },
    )->name('lists.show');

    Route::get('/copies/{copy}/edit', function (Copy $copy) {
        $copy->loadMissing('media');

        Gate::authorize('view', $copy->media);
        Gate::authorize('update', $copy);

        return view('copies.edit', [
            'copy' => $copy,
        ]);
    })->name('copies.edit');

    Route::view('/locations', 'locations.index')
        ->name('locations.index');

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with(
                'status',
                'Du wurdest abgemeldet.',
            );
    })->name('logout');
});

Route::get(
    '/health/ready',
    [HealthController::class, 'ready'],
)->name('health.ready');
