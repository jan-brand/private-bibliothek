<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Library extends Model
{
    public const TYPE_SHARED = 'shared';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'type',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'owner_user_id' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(LibraryMembership::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'library_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function copies(): HasMany
    {
        return $this->hasMany(Copy::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function mediaLists(): HasMany
    {
        return $this->hasMany(MediaList::class);
    }

    public function borrowers(): HasMany
    {
        return $this->hasMany(Borrower::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * @param  Builder<Library>  $query
     * @return Builder<Library>
     */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdministrator()) {
            return $query;
        }

        return $query->whereHas(
            'memberships',
            function (Builder $query) use ($user): void {
                $query->where('user_id', $user->getKey());
            },
        );
    }

    public function isShared(): bool
    {
        return true;
    }
}
