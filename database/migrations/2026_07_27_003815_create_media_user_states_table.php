<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_user_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_id')
                ->constrained('media')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('status', 30);
            $table->date('started_at')->nullable();
            $table->date('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['media_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_user_states');
    }
};
