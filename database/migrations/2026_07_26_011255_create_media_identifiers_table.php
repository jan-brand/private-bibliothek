<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_identifiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_id')
                ->constrained('media')
                ->cascadeOnDelete();
            $table->string('scheme', 24);
            $table->string('value');
            $table->string('normalized_value');
            $table->string('label')->nullable();
            $table->timestamps();

            $table->unique(['media_id', 'scheme', 'normalized_value']);
            $table->index(['scheme', 'normalized_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_identifiers');
    }
};
