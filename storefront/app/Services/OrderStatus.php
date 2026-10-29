<?php

namespace Storefront\Services;

final class OrderStatus
{
    public const PAYMENT_LABELS = ['unpaid' => 'Unpaid', 'paid' => 'Paid'];

    public static function canonical(string $status): string
    {
        return $status;
    }

    public static function label(string $status): string
    {
        return ucwords(str_replace('_', ' ', $status));
    }

    public static function tone(string $status): string
    {
        return match ($status) {
            'delivered' => 'success','cancelled','returned' => 'danger',default => 'info'
        };
    }

    public static function customerActions(string $status): array
    {
        return match ($status) {
            'pending','pending_confirmation','future_order','confirmed','processing','ready_to_ship' => ['cancelled'],'delivered' => ['return_requested'],default => []
        };
    }
}
