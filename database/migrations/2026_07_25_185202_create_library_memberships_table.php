<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('library_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('role', 16)->default('member')->index();
            $table->timestamps();

            $table->unique(['library_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_memberships');
    }
};
