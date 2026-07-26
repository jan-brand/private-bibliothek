<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('copies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('library_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('media_id')
                ->constrained('media')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('inventory_code')->nullable();
            $table->string('barcode')->nullable();
            $table->string('condition', 24)->default('good')->index();
            $table->string('status', 24)->default('available')->index();
            $table->date('acquired_at')->nullable();
            $table->string('acquisition_source')->nullable();
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('updated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(['library_id', 'inventory_code']);
            $table->unique(['library_id', 'barcode']);
            $table->index(['library_id', 'media_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('copies');
    }
};
