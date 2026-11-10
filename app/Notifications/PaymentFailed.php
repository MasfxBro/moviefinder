<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class PaymentFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [60, 300, 900, 3600];

    public function __construct(public Subscription $subscription, public Carbon $graceEndsAt) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Payment failed')->greeting('Hello '.$notifiable->name.',')
            ->line('We could not process your subscription payment. Your paid access remains available until '.$this->graceEndsAt->toFormattedDateString().'.')
            ->line('Please update your payment method before the grace period ends.');
    }
}
