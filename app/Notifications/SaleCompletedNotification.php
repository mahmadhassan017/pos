<?php

namespace App\Notifications;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SaleCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(public Sale $sale)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'sale_id' => $this->sale->id,
            'invoice_no' => $this->sale->invoice_no,
            'total' => $this->sale->total,
            'payment_method' => $this->sale->payment_method,
            'cashier' => $this->sale->cashier?->name ?? 'Guest cashier',
            'message' => "New sale {$this->sale->invoice_no} completed.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New sale: {$this->sale->invoice_no}")
            ->line("A sale has been completed for {$this->sale->total}.")
            ->line("Payment method: {$this->sale->payment_method}")
            ->action('View sales report', url('/admin/reports/sales'));
    }
}
