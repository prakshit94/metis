<?php

namespace App\Services;

use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Orders\Models\Order;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;

/** Reuses the order directory's existing role, status, creator and LOB rules. */
class RecordVisibility
{
    public function globalViewer(): bool
    {
        $user = auth()->user();

        return $user && ($user->hasAnyRole(['Super Admin', 'Admin']) || $user->can('view-all-data'));
    }

    public function orders(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->hasAnyRole(['Super Admin', 'Admin']) || $user->can('view-all-data')) {
            return $query;
        }
        if ($state = $user->lob_state_name) {
            $query->where(fn ($q) => $q->where('shipping_state', $state)->orWhere('created_by', $user->id));
        }
        $statuses = [];
        foreach (['future_order', 'pending', 'confirmed', 'processing', 'ready_to_ship', 'dispatched', 'delivered', 'return_requested', 'returned', 'cancelled'] as $status) {
            if ($user->can('orders.view.'.$status)) {
                $statuses[] = $status;
            }
        }
        if (in_array('pending', $statuses, true)) {
            $statuses[] = 'pending_confirmation';
        }
        if ($statuses === [] && $user->can('view_all_order')) {
            $statuses = array_merge(Order::LIFECYCLE_STATUSES, ['cancelled', 'returned', 'return_requested']);
        }
        $query->whereIn('status', $statuses);
        if (! $user->can('view_all_order')) {
            $query->where('created_by', $user->id);
        }

        return $query;
    }

    /** Match the customer directory's existing ownership and state rules. */
    public function customers(Builder $query, ?User $user): Builder
    {
        if (! $user) return $query->whereRaw('1 = 0');
        $global = $user->hasAnyRole(['Super Admin', 'Admin']) || $user->can('view-all-data') || $user->can('view_all_customer');
        return $query->when(! $global, fn ($q) => $q->where('created_by', $user->id))
            ->when($user->lob_state_name, fn ($q, $state) => $q->whereHas('addresses', fn ($address) => $address->where('state', $state)->where('is_default', true)));
    }

    public function assertCustomer(int $id): void
    {
        abort_unless($this->customers(\App\Modules\Customers\Models\Party::query(), auth()->user())->whereKey($id)->exists(), 404);
    }

    public function assertOrder(int $id): void
    {
        abort_unless($this->orders($this->globalViewer() ? Order::withTrashed() : Order::query(), auth()->user())->whereKey($id)->exists(), 404);
    }

    public function financial(Builder $query): Builder
    {
        if ($this->globalViewer()) {
            return $query;
        }

        return $query->whereHas('order', fn ($q) => $this->orders($q, auth()->user()));
    }

    public function creditNotes(Builder $query): Builder
    {
        if ($this->globalViewer()) {
            return $query;
        }

        return $query->where(function ($q) {
            $q->whereHas('invoice.order', fn ($order) => $this->orders($order, auth()->user()))
                ->orWhereHas('orderReturn.order', fn ($order) => $this->orders($order, auth()->user()));
        });
    }

    public function assertWarehouse(int $id): void
    {
        if ($state = auth()->user()?->lob_state_name) {
            abort_unless(Warehouse::withTrashed()->whereKey($id)->where('state', $state)->exists(), 404);
        }
    }
}
