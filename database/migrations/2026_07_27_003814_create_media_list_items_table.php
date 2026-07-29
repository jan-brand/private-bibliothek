<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_list_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_list_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('media_id')
                ->constrained('media')
                ->cascadeOnDelete();
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();

            $table->unique(['media_list_id', 'media_id']);
            $table->index(['media_list_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_list_items');
    }
};
