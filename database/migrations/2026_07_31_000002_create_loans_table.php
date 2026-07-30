<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('library_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('copy_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('borrower_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('loaned_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('returned_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestampTz('loaned_at');
            $table->date('due_at')->nullable();
            $table->timestampTz('returned_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('return_notes')->nullable();
            $table->timestamps();

            $table->index(['library_id', 'returned_at', 'due_at']);
            $table->index(['borrower_id', 'returned_at']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX loans_one_active_per_copy_index
             ON loans (copy_id)
             WHERE returned_at IS NULL',
        );

        DB::statement(
            'ALTER TABLE loans
             ADD CONSTRAINT loans_due_at_check
             CHECK (due_at IS NULL OR due_at >= loaned_at::date)',
        );

        DB::statement(
            'ALTER TABLE loans
             ADD CONSTRAINT loans_returned_at_check
             CHECK (returned_at IS NULL OR returned_at >= loaned_at)',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
