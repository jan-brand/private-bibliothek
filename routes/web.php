<?php

use App\Http\Controllers\HealthController;
use App\Models\Copy;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'auth.login')->name('login');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::view('/media', 'media.index')->name('media.index');
    Route::view('/media/create', 'media.create')->name('media.create');
    Route::get('/media/{media}/edit', function (Media $media) {
        return view('media.edit', ['media' => $media]);
    })->name('media.edit');
    Route::get('/media/{media}', function (Media $media) {
        return view('media.show', ['media' => $media]);
    })->name('media.show');

    Route::get('/copies/{copy}/edit', function (Copy $copy) {
        return view('copies.edit', ['copy' => $copy]);
    })->name('copies.edit');

    Route::view('/locations', 'locations.index')->name('locations.index');

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'Du wurdest abgemeldet.');
    })->name('logout');
});

Route::get('/health/ready', [HealthController::class, 'ready'])
    ->name('health.ready');
