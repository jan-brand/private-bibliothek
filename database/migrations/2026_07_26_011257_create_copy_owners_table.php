<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('copy_owners', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('copy_id')
                ->constrained('copies')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['copy_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('copy_owners');
    }
};
