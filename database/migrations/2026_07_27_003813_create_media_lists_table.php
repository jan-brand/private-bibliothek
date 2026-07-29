<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_lists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('library_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('owner_user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('visibility', 20)->default('private');
            $table->timestamps();

            $table->index(['library_id', 'visibility']);
            $table->index(['owner_user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_lists');
    }
};
