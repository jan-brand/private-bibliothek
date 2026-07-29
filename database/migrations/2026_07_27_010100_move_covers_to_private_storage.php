<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('media')
            ->whereNotNull('cover_path')
            ->orderBy('id')
            ->get(['id', 'cover_path'])
            ->each(function (object $media): void {
                $path = trim((string) $media->cover_path);

                if (
                    $path === ''
                    || Storage::disk('local')->exists($path)
                    || ! Storage::disk('public')->exists($path)
                ) {
                    return;
                }

                $contents = Storage::disk('public')->get($path);

                if (Storage::disk('local')->put($path, $contents)) {
                    Storage::disk('public')->delete($path);
                }
            });
    }

    public function down(): void
    {
        DB::table('media')
            ->whereNotNull('cover_path')
            ->orderBy('id')
            ->get(['id', 'cover_path'])
            ->each(function (object $media): void {
                $path = trim((string) $media->cover_path);

                if (
                    $path === ''
                    || Storage::disk('public')->exists($path)
                    || ! Storage::disk('local')->exists($path)
                ) {
                    return;
                }

                $contents = Storage::disk('local')->get($path);

                if (Storage::disk('public')->put($path, $contents)) {
                    Storage::disk('local')->delete($path);
                }
            });
    }
};
