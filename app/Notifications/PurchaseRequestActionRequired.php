<?php

namespace App\Notifications;

use App\Models\PurchaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseRequestActionRequired extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public PurchaseRequest $purchaseRequest, public string $stepName)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = 'Action Required: ' . strtoupper(str_replace('_', ' ', $this->stepName)) . ' for ' . $this->purchaseRequest->pr_number;
        $url = match ($this->stepName) {
            'budget_office_earmarking' => route('budget.purchase-requests.index'),
            'ceo_initial_approval' => route('ceo.purchase-requests.index'),
            default => route('supply.purchase-requests.index'),
        };

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A Purchase Request requires your action: ' . $this->purchaseRequest->pr_number)
            ->line('Step: ' . str_replace('_', ' ', $this->stepName))
            ->action('Open Queue', url($url))
            ->line('Thank you.');
    }
}


