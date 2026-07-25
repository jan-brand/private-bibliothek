<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

final class HealthController extends Controller
{
    public function ready(): JsonResponse
    {
        try {
            DB::select('select 1');

            return response()->json([
                'status' => 'ready',
                'database' => 'available',
                'checked_at' => now()->toIso8601String(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'not_ready',
                'database' => 'unavailable',
                'checked_at' => now()->toIso8601String(),
            ], 503);
        }
    }
}
