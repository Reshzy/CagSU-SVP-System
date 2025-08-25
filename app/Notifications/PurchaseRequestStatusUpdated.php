<?php

namespace App\Notifications;

use App\Models\PurchaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseRequestStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public PurchaseRequest $purchaseRequest, public string $fromStatus, public string $toStatus)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('PR Status Updated: ' . $this->purchaseRequest->pr_number)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your Purchase Request status changed from ' . str_replace('_', ' ', $this->fromStatus) . ' to ' . str_replace('_', ' ', $this->toStatus) . '.')
            ->line('PR Number: ' . $this->purchaseRequest->pr_number)
            ->action('View My PRs', url(route('purchase-requests.index')))
            ->line('Thank you.');
    }
}


