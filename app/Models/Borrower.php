<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Borrower extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'library_id',
        'name',
        'email',
        'phone',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'library_id' => 'integer',
        ];
    }

    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function activeLoans(): HasMany
    {
        return $this->hasMany(Loan::class)
            ->whereNull('returned_at');
    }
}
