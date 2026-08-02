<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class HealthController extends Controller
{
    public function ready(): JsonResponse
    {
        $databaseAvailable = $this->databaseIsAvailable();
        $privateStorageWritable = $this->privateStorageIsWritable();
        $ready = $databaseAvailable && $privateStorageWritable;

        return response()->json([
            'status' => $ready ? 'ready' : 'not_ready',
            'database' => $databaseAvailable ? 'available' : 'unavailable',
            'storage' => $privateStorageWritable ? 'writable' : 'unavailable',
            'checked_at' => now()->toIso8601String(),
        ], $ready ? 200 : 503);
    }

    private function databaseIsAvailable(): bool
    {
        try {
            DB::select('select 1');

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    private function privateStorageIsWritable(): bool
    {
        $disk = null;
        $probePath = 'health/readiness-'.Str::uuid().'.tmp';

        try {
            $disk = Storage::disk('local');

            if (! $disk->put($probePath, 'ready')) {
                return false;
            }

            return $disk->exists($probePath);
        } catch (Throwable $exception) {
            report($exception);

            return false;
        } finally {
            $this->removeStorageProbe($disk, $probePath);
        }
    }

    private function removeStorageProbe(?Filesystem $disk, string $probePath): void
    {
        if ($disk === null) {
            return;
        }

        try {
            $disk->delete($probePath);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
