<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/** Read-only attribution for orders already selected through their visibility scope. */
class OrderAttribution
{
    public function attach(Collection $orders): void
    {
        if ($orders->isEmpty()) {
            return;
        }
        $orders->loadMissing('creator:id,name,email,photo');
        $checkouts = DB::table('storefront_checkouts as checkout')
            ->leftJoin('customer_accounts as account', 'account.id', '=', 'checkout.customer_account_id')
            ->leftJoin('parties as customer', 'customer.id', '=', 'account.customer_id')
            ->whereIn('checkout.order_id', $orders->modelKeys())
            ->get(['checkout.order_id', 'customer.firstname', 'customer.middlename', 'customer.lastname', 'customer.company_name', 'customer.avatar'])
            ->keyBy('order_id');

        foreach ($orders as $order) {
            if ($checkout = $checkouts->get($order->id)) {
                $name = trim(implode(' ', array_filter([$checkout->firstname, $checkout->middlename, $checkout->lastname])));
                $attribution = ['name' => $name ?: ($checkout->company_name ?: 'Customer'), 'email' => '', 'photo' => $checkout->avatar, 'type' => 'customer', 'source' => 'shop'];
            } else {
                $attribution = ['name' => $order->creator?->name ?: 'Not recorded', 'email' => $order->creator?->email ?? '', 'photo' => $order->creator?->photo, 'type' => $order->creator ? 'staff' : 'unknown', 'source' => $order->creator ? 'admin' : null];
            }
            $order->setAttribute('placed_by', $attribution);
        }
    }
}
