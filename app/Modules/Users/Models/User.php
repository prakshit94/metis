<?php

declare(strict_types=1);

namespace App\Modules\Users\Models;

use App\Models\Chat\Presence;
use App\Modules\Catalog\Models\Service;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Auditable
{
    /** @use HasFactory<UserFactory> */
    use AuditableTrait, HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable, SoftDeletes;

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
        'employee_id',
        'photo',
        'joining_date',
        'password',
        'is_active',
        'email_verified_at',
        'phone',
        'department',
        'password_changed_at',
        'suspended_until',
        'address_line_1',
        'address_line_2',
        'village_id',
        'village_name',
        'post_office',
        'taluka',
        'district',
        'city',
        'state',
        'pincode',
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
            'email_verified_at' => 'datetime',
            'joining_date' => 'date',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'password_changed_at' => 'datetime',
            'suspended_until' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * Get all login history records for this user.
     */
    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    /**
     * Shipping services this user provides or manages.
     */
    public function providedShippingServices(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_provider_users')
            ->withTimestamps();
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

    public function readActivities(): BelongsToMany
    {
        return $this->belongsToMany(
            Activity::class,
            'user_read_activities',
            'user_id',
            'activity_id'
        )->withTimestamps();
    }

    public function chatPresence()
    {
        return $this->hasOne(Presence::class);
    }

    public function isOnline(): bool
    {
        return DB::table('sessions')
            ->where('user_id', $this->id)
            ->where('last_activity', '>=', now()->subMinutes(5)->getTimestamp())
            ->exists();
    }

    public function getLastSeenAt(): ?Carbon
    {
        $lastActivity = DB::table('sessions')
            ->where('user_id', $this->id)
            ->max('last_activity');

        return $lastActivity ? now()->setTimestamp((int) $lastActivity) : null;
    }

    public function getActiveDevice(): ?string
    {
        $session = DB::table('sessions')
            ->where('user_id', $this->id)
            ->latest('last_activity')
            ->first();

        if (! $session) {
            return null;
        }

        $ua = $session->user_agent;
        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }
}
