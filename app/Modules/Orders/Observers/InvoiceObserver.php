<?php

namespace App\Modules\Orders\Observers;

use App\Modules\Orders\Models\Invoice;
use App\Services\TargetAchievementService;

class InvoiceObserver
{
    public function __construct(private TargetAchievementService $service) {}

    public function saved(Invoice $invoice): void
    {
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
}
