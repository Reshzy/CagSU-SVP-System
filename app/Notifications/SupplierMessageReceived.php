<?php

namespace App\Notifications;

use App\Models\SupplierMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupplierMessageReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupplierMessage $message)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Supplier Message: ' . ($this->message->subject))
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A supplier has sent a message' . ($this->message->purchase_request_id ? ' regarding PR ' : '') . ($this->message->purchaseRequest?->pr_number ?? ''))
            ->line('From: ' . ($this->message->supplier_name ?: 'Unknown') . ' <' . $this->message->supplier_email . '>')
            ->line('Subject: ' . $this->message->subject)
            ->line('Message:')
            ->line($this->message->message_body)
            ->line('Thank you.');
    }
}


