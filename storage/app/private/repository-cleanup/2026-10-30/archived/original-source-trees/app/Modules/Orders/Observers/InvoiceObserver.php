<?php

namespace App\Modules\Orders\Observers;

use App\Modules\Orders\Models\Invoice;
use App\Modules\Orders\Models\Order;
use App\Modules\Users\Models\User;
use App\Services\TargetAchievementService;
use Carbon\Carbon;

class InvoiceObserver
{
    public function __construct(private TargetAchievementService $service) {}

    public function saved(Invoice $invoice): void
    {
        if (!$invoice->wasRecentlyCreated && $invoice->wasChanged(['order_id', 'invoice_date'])) {
            $this->recalculatePreviousScope($invoice);
        }
        $this->updateTargets($invoice);
    }

    public function deleted(Invoice $invoice): void
    {
        $this->updateTargets($invoice);
    }

    public function restored(Invoice $invoice): void
    {
        $this->updateTargets($invoice);
    }

    private function updateTargets(Invoice $invoice): void
    {
        $invoice->loadMissing('order.creator');

        $order = $invoice->order;
        if (!$order) {
            return;
        }

        $user = $order->creator;
        if (!$user) {
            return;
        }

        $invoiceDate = $invoice->invoice_date ?? $invoice->created_at;
        if (!$invoiceDate) {
            return;
        }

        $teamId = $this->service->resolveLobTeamId($user);

        $this->service->recalculateForUser(
            userId:       $user->id,
            departmentId: $user->department_id,
            teamId:       $teamId,
            date:         \Carbon\Carbon::parse($invoiceDate),
            metricTypes:  ['invoice_collection'],
        );
    }

    private function recalculatePreviousScope(Invoice $invoice): void
    {
        $order = Order::withTrashed()->find($invoice->getRawOriginal('order_id'));
        $user = $order ? User::withTrashed()->find($order->created_by) : null;
        $date = $invoice->getRawOriginal('invoice_date') ?: $invoice->getRawOriginal('created_at');

        if (!$user || !$date) {
            return;
        }

        $this->service->recalculateForUser(
            userId: $user->id,
            departmentId: $user->department_id,
            teamId: $this->service->resolveLobTeamId($user),
            date: Carbon::parse($date),
            metricTypes: ['invoice_collection'],
        );
    }
}
