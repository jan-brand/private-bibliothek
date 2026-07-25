<?php

use App\Jobs\QueueSmokeTest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Artisan::command('minibib:queue-smoke {--sync}', function (): int {
    $token = (string) Str::uuid();

    if ((bool) $this->option('sync')) {
        QueueSmokeTest::dispatchSync($token);
        $this->info('Queue-Smoke-Test wurde synchron ausgeführt.');
    } else {
        QueueSmokeTest::dispatch($token);
        $this->info('Queue-Smoke-Test wurde in die Queue eingestellt.');
    }

    $this->line("Token: {$token}");

    return 0;
})->purpose('Prüft die Verarbeitung eines Laravel Queue Jobs.');

Artisan::command('minibib:scheduler-smoke', function (): int {
    Storage::disk('local')->put(
        'health/scheduler-last-run.json',
        json_encode([
            'status' => 'processed',
            'processed_at' => now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );

    $this->info('Scheduler-Smoke-Test wurde ausgeführt.');

    return 0;
})->purpose('Prüft die Ausführung geplanter Laravel-Aufgaben.');

Schedule::command('minibib:scheduler-smoke')
    ->everyMinute()
    ->environments(['local', 'testing'])
    ->withoutOverlapping();

Schedule::command('minibib:scheduler-smoke')
    ->dailyAt('03:05')
    ->environments(['production'])
    ->withoutOverlapping();
