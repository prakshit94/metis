<?php

declare(strict_types=1);

namespace App\Modules\Users\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'password',
        'phone',
        'department',
        'is_active',
        'password_changed_at',
        'suspended_until',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed',
            'is_active'          => 'boolean',
            'password_changed_at' => 'datetime',
            'suspended_until'    => 'datetime',
            'deleted_at'         => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * Get all login history records for this user.
     */
    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    // ─── Helper Methods ───────────────────────────────────────────────────────

    /**
     * Determine whether the user account is active.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Determine whether the user is currently suspended.
     */
    public function isSuspended(): bool
    {
        return $this->suspended_until !== null
            && $this->suspended_until->isFuture();
    }

    /**
     * Suspend the user for a given number of minutes.
     */
    public function suspend(int $minutes = 15): void
    {
        $this->update(['suspended_until' => Carbon::now()->addMinutes($minutes)]);
    }

    /**
     * Lift an active suspension from the user.
     */
    public function unsuspend(): void
    {
        $this->update(['suspended_until' => null]);
    }
}
