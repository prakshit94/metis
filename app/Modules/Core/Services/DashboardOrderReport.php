<?php

namespace App\Modules\Core\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/** Read-only aggregation of the dashboard's already authorized, date-filtered orders. */
class DashboardOrderReport
{
    public function build(Builder $scopedOrders): array
    {
        $orders = (clone $scopedOrders)->where('orders.type', 'sale')->where('orders.status', '!=', 'cancelled')->reorder()->select([
            'orders.id', 'orders.status', 'orders.office_id', 'orders.shipping_state',
            'orders.total_amount', 'orders.tax_amount', 'orders.discount_amount',
            'orders.net_amount', 'orders.wallet_amount_used',
        ])->selectRaw("EXISTS(SELECT 1 FROM order_returns r WHERE r.order_id = orders.id AND r.status = 'completed') AS has_completed_return")
            ->selectRaw("(SELECT COALESCE(SUM(p.amount),0) FROM payments p WHERE p.order_id = orders.id AND p.status = 'completed' AND p.deleted_at IS NULL) AS paid")
            ->selectRaw("(SELECT COALESCE(SUM(f.amount),0) FROM refunds f WHERE f.order_id = orders.id AND f.status = 'completed') AS refunded");
        // One row per state / office / status; no item or shipment join can multiply amounts.
        $query = DB::query()->fromSub($orders->toBase(), 'o')->leftJoin('offices as office', 'office.id', '=', 'o.office_id');
        $state = "COALESCE(NULLIF(TRIM(o.shipping_state), ''), 'Unassigned')";
        $rows = $query->selectRaw("$state AS state, o.office_id, COALESCE(office.name, 'Unassigned') AS office, o.status, o.has_completed_return, COUNT(*) AS orders")
            ->selectRaw('SUM(o.total_amount) AS gross, SUM(o.discount_amount) AS discount, SUM(o.tax_amount) AS tax, SUM(o.net_amount) AS payable, SUM(o.wallet_amount_used) AS wallet, SUM(o.net_amount + o.wallet_amount_used) AS value, SUM(o.paid) AS paid, SUM(o.refunded) AS refunded, SUM(GREATEST(o.net_amount - o.paid, 0)) AS outstanding')
            ->groupByRaw("$state, o.office_id, office.name, o.status, o.has_completed_return")->get();
        $fields = ['gross','discount','tax','payable','wallet','value','paid','refunded','outstanding','booked','delivered','dispatched'];
        $empty = fn () => array_fill_keys(['orders', ...$fields], 0);
        $totals = $empty(); $states = []; $offices = []; $statuses = [];
        foreach ($rows as $row) {
            $values = $empty(); $values['orders'] = (int) $row->orders;
            foreach (array_slice($fields, 0, 9) as $field) $values[$field] = (int) round((float) $row->$field * 100);
            $eligible = !in_array($row->status, ['cancelled','future_order','returned'], true) && !$row->has_completed_return;
            $values['booked'] = $eligible ? $values['payable'] : 0;
            $values['delivered'] = $eligible && in_array($row->status, ['delivered','completed'], true) ? $values['payable'] : 0;
            $values['dispatched'] = $eligible && $row->status === 'dispatched' ? $values['payable'] : 0;
            $officeKey = $row->office_id === null ? 'unassigned' : (string) $row->office_id;
            $states[$row->state] ??= ['name'=>$row->state, ...$empty()];
            $offices[$officeKey] ??= ['id'=>$row->office_id, 'name'=>$row->office, ...$empty()];
            $statuses[$row->status] ??= ['name'=>$row->status, ...$empty()];
            foreach (['orders', ...$fields] as $field) {
                $totals[$field] += $values[$field];
                $states[$row->state][$field] += $values[$field];
                $offices[$officeKey][$field] += $values[$field];
                $statuses[$row->status][$field] += $values[$field];
            }
        }
        $money = function ($row) use ($fields) { foreach ($fields as $field) $row[$field] /= 100; return $row; };
        $sort = function ($groups) use ($money) {
            $groups = array_map($money, array_values($groups));
            usort($groups, fn ($a,$b) => ($b['booked'] <=> $a['booked']) ?: strcmp($a['name'],$b['name']));
            return $groups;
        };
        return ['totals'=>$money($totals), 'states'=>$sort($states), 'offices'=>$sort($offices), 'statuses'=>$sort($statuses)];
    }
}
