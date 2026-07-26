<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'is_admin',
        'is_active',
        'last_login_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function personalLibrary(): HasOne
    {
        return $this->hasOne(Library::class, 'owner_user_id')
            ->where('type', Library::TYPE_PRIVATE);
    }

    public function libraryMemberships(): HasMany
    {
        return $this->hasMany(LibraryMembership::class);
    }

    public function libraries(): BelongsToMany
    {
        return $this->belongsToMany(Library::class, 'library_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function createdMedia(): HasMany
    {
        return $this->hasMany(Media::class, 'created_by_user_id');
    }

    public function ownedCopies(): BelongsToMany
    {
        return $this->belongsToMany(Copy::class, 'copy_owners')
            ->withTimestamps();
    }

    public function createdLocations(): HasMany
    {
        return $this->hasMany(Location::class, 'created_by_user_id');
    }

    public function isAdministrator(): bool
    {
        return (bool) $this->is_admin;
    }
}
