<?php

declare(strict_types=1);

namespace App\Modules\Users\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Audit log for every authentication attempt.
 *
 * Records are automatically pruned via MassPrunable after 90 days to manage
 * table growth in enterprise environments (no individual model events fired).
 */
class LoginHistory extends Model
{
    use MassPrunable;

    /**
     * Indicates if the model should be timestamped.
     * We use a single `attempted_at` column instead.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'email_attempted',
        'ip_address',
        'user_agent',
        'device_type',
        'status',
        'failure_reason',
        'attempted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attempted_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * The user associated with this login attempt (nullable for
     * attempts against non-existent email addresses).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Pruning ──────────────────────────────────────────────────────────────

    /**
     * Define the prunable query — removes records older than 90 days.
     *
     * Schedule via:
     *   $schedule->command('model:prune')->daily();
     */
    public function prunable(): Builder
    {
        return static::query()
            ->where('attempted_at', '<', Carbon::now()->subDays(90));
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Scope to only successful logins.
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope to only failed logins.
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to recent failed attempts for a given email/IP pair.
     */
    public function scopeRecentFailedFor(Builder $query, string $email, string $ip, int $minutes = 5): Builder
    {
        return $query
            ->where(function (Builder $q) use ($email, $ip) {
                $q->where('email_attempted', $email)
                    ->orWhere('ip_address', $ip);
            })
            ->where('status', 'failed')
            ->where('failure_reason', '!=', 'throttled')
            ->where('attempted_at', '>=', Carbon::now()->subMinutes($minutes));
    }
}
