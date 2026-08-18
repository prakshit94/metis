<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    public $productName;
    public $currentStock;
    public $warehouseName;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $productName, int $currentStock, string $warehouseName)
    {
        $this->productName = $productName;
        $this->currentStock = $currentStock;
        $this->warehouseName = $warehouseName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'title' => 'Low Stock Alert',
            'message' => "Stock for {$this->productName} at {$this->warehouseName} has dropped to {$this->currentStock}.",
            'product_name' => $this->productName,
            'current_stock' => $this->currentStock,
            'warehouse' => $this->warehouseName,
        ];
    }
}
