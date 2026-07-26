<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->string('cover_path')->nullable()->after('cover_url');
            $table->string('cover_mime_type', 100)->nullable()->after('cover_path');
            $table->text('cover_source_url')->nullable()->after('cover_mime_type');
            $table->timestampTz('cover_updated_at')->nullable()->after('cover_source_url');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->dropColumn([
                'cover_path',
                'cover_mime_type',
                'cover_source_url',
                'cover_updated_at',
            ]);
        });
    }
};
