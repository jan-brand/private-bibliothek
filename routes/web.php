<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::get('/health/ready', [HealthController::class, 'ready'])
    ->name('health.ready');
