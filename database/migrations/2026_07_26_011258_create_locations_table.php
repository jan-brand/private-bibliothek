<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('library_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('locations')
                ->cascadeOnDelete();
            $table->string('type', 24)->index();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamps();

            $table->index(['library_id', 'parent_id', 'sort_order']);
        });

        Schema::table('copies', function (Blueprint $table): void {
            $table->foreign('location_id')
                ->references('id')
                ->on('locations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('copies', function (Blueprint $table): void {
            $table->dropForeign(['location_id']);
        });

        Schema::dropIfExists('locations');
    }
};
