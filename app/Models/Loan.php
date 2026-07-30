<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'library_id',
        'copy_id',
        'borrower_id',
        'loaned_by_user_id',
        'returned_by_user_id',
        'loaned_at',
        'due_at',
        'returned_at',
        'notes',
        'return_notes',
    ];

    protected function casts(): array
    {
        return [
            'library_id' => 'integer',
            'copy_id' => 'integer',
            'borrower_id' => 'integer',
            'loaned_by_user_id' => 'integer',
            'returned_by_user_id' => 'integer',
            'loaned_at' => 'immutable_datetime',
            'due_at' => 'immutable_date',
            'returned_at' => 'immutable_datetime',
        ];
    }

    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }

    public function copy(): BelongsTo
    {
        return $this->belongsTo(Copy::class);
    }

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(Borrower::class);
    }

    public function loanedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'loaned_by_user_id');
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by_user_id');
    }

    /**
     * @param  Builder<Loan>  $query
     * @return Builder<Loan>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('returned_at');
    }

    /**
     * @param  Builder<Loan>  $query
     * @return Builder<Loan>
     */
    public function scopeReturned(Builder $query): Builder
    {
        return $query->whereNotNull('returned_at');
    }

    /**
     * @param  Builder<Loan>  $query
     * @return Builder<Loan>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->whereNull('returned_at')
            ->whereNotNull('due_at')
            ->whereDate('due_at', '<', today());
    }

    public function isActive(): bool
    {
        return $this->returned_at === null;
    }

    public function isOverdue(): bool
    {
        if (! $this->isActive() || $this->due_at === null) {
            return false;
        }

        return CarbonImmutable::parse((string) $this->due_at)
            ->isBefore(today());
    }

    public function statusLabel(): string
    {
        if ($this->returned_at !== null) {
            return 'Zurückgegeben';
        }

        if ($this->isOverdue()) {
            return 'Überfällig';
        }

        return 'Aktiv';
    }
}
