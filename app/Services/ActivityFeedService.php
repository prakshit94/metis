<?php

namespace App\Services;

use App\Modules\Customers\Models\Party;
use App\Modules\Customers\Models\PartyAddress;
use App\Modules\Inventory\Models\Stock;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Activitylog\Models\Activity;

class ActivityFeedService
{
    public function visibleQuery(User $user): Builder
    {
        $query = Activity::query();
        if (! $user->hasRole('Super Admin')) {
            $query->where(function ($query) use ($user) {
                $query->where('causer_id', $user->id)
                    ->orWhere(function ($orders) use ($user) {
                        $orders->where('subject_type', Order::class)
                            ->whereIn('subject_id', Order::where('created_by', $user->id)->select('id'));
                    });
            });
        }

        return $query;
    }

    public function snapshot(User $user): array
    {
        $activities = $this->visibleQuery($user)->with(['causer', 'subject'])->latest()->limit(50)->get();
        $this->prepareDescriptions($activities);
        $readIds = $activities->isEmpty() ? [] : $user->readActivities()
            ->whereIn('activity_id', $activities->modelKeys())->pluck('activity_id')->all();

        return ['activities' => $activities, 'readIds' => $readIds, 'count' => $this->unreadCount($user)];
    }

    public function unreadCount(User $user): int
    {
        // Keep the existing 500-newest-ID window; count in SQL rather than
        // transferring two ID lists and diffing them for every poll/page load.
        $recent = $this->visibleQuery($user)->select('id')->latest('id')->limit(500);
        $reads = $user->readActivities();

        return (new Activity)->getConnection()->query()->fromSub($recent, 'recent_activity')
            ->whereNotExists(function ($query) use ($user, $reads) {
                $query->selectRaw('1')->from($reads->getTable())
                    ->whereColumn($reads->getRelatedPivotKeyName(), 'recent_activity.id')
                    ->where($reads->getForeignPivotKeyName(), $user->id);
            })->count();
    }

    public function markRead(User $user, array $ids): void
    {
        if ($ids === []) {
            return;
        }
        $relation = $user->readActivities();
        $now = $user->freshTimestampString();
        $rows = array_map(fn ($id) => [
            $relation->getForeignPivotKeyName() => $user->id,
            $relation->getRelatedPivotKeyName() => $id,
            'created_at' => $now,
            'updated_at' => $now,
        ], array_unique($ids));

        // The existing unique(user_id, activity_id) key keeps this idempotent.
        // Existing read timestamps are left intact, as with syncWithoutDetaching.
        foreach (array_chunk($rows, 1000) as $chunk) {
            $relation->newPivotStatement()->insertOrIgnore($chunk);
        }
    }

    public function prepareAudits(Collection $audits): void
    {
        $audits->loadMorph('auditable', [
            Stock::class => ['product'],
            OrderItem::class => ['product', 'order'],
            PartyAddress::class => ['party'],
        ]);
        $partyIds = [];
        foreach ($audits as $audit) {
            if (class_basename($audit->auditable_type) === 'PartyAddress' && ! $audit->auditable?->party?->name) {
                $partyId = $audit->new_values['party_id'] ?? $audit->old_values['party_id'] ?? null;
                if ($partyId) {
                    $partyIds[$audit->id] = $partyId;
                }
            }
        }
        $parties = $partyIds ? Party::whereKey(array_values(array_unique($partyIds)))->get()->keyBy('id') : collect();
        foreach ($audits as $audit) {
            if (class_basename($audit->auditable_type) === 'PartyAddress') {
                $audit->setRelation('feedAddressParty', $parties->get($partyIds[$audit->id] ?? null))->makeHidden('feedAddressParty');
            }
        }
    }

    public function prepareDescriptions(Collection $activities): void
    {
        if ($activities->isEmpty()) {
            return;
        }
        $activities->loadMorph('subject', [
            Stock::class => ['product'],
            OrderItem::class => ['product', 'order'],
            PartyAddress::class => ['party'],
        ]);

        $addressPartyIds = [];
        $lookups = null;
        foreach ($activities as $activity) {
            $subjectName = class_basename($activity->subject_type);
            if ($subjectName === 'PartyAddress' && ! $activity->subject?->party?->name) {
                $properties = $activity->properties;
                $partyId = $properties['attributes']['party_id'] ?? $properties['old']['party_id'] ?? null;
                if ($partyId) {
                    $addressPartyIds[$activity->id] = $partyId;
                }
            }
            // The original formatter skips stock context when properties are
            // absent; old/imported rows may also have no created_at timestamp.
            if ($subjectName !== 'Stock' || ! $activity->properties) {
                continue;
            }

            // Run the same per-entry time/causer lookup in one round trip.
            // Each scalar subquery retains the original first-match semantics.
            $relatedOrder = Activity::query()->select('id')
                ->where('subject_type', Order::class)
                ->where('causer_id', $activity->causer_id)
                ->whereBetween('created_at', [
                    $activity->created_at->copy()->subSeconds(2),
                    $activity->created_at->copy()->addSeconds(2),
                ])->limit(1);
            $lookup = $activity->getConnection()->query()
                ->selectRaw('? as feed_activity_id', [$activity->id])
                ->selectSub($relatedOrder, 'related_activity_id');
            $lookups = $lookups ? $lookups->unionAll($lookup) : $lookup;
        }

        if ($lookups) {
            $matches = $lookups->get()->keyBy('feed_activity_id');
            $ids = $matches->pluck('related_activity_id')->filter()->unique()->all();
            $orders = $ids ? Activity::with('subject')->whereKey($ids)->get()->keyBy('id') : collect();
            foreach ($activities as $activity) {
                if (class_basename($activity->subject_type) === 'Stock') {
                    $activity->setRelation('feedRelatedOrderActivity', $orders->get($matches->get($activity->id)?->related_activity_id))->makeHidden('feedRelatedOrderActivity');
                }
            }
        }

        $parties = $addressPartyIds ? Party::whereKey(array_values(array_unique($addressPartyIds)))->get()->keyBy('id') : collect();
        foreach ($activities as $activity) {
            if (class_basename($activity->subject_type) === 'PartyAddress') {
                $activity->setRelation('feedAddressParty', $parties->get($addressPartyIds[$activity->id] ?? null))->makeHidden('feedAddressParty');
            }
        }
    }
}
