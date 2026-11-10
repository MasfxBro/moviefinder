<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RenewalReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [60, 300, 900, 3600];

    public function __construct(public Subscription $subscription, public ?string $paymentUrl = null) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject('Subscription renewal reminder')->greeting('Hello '.$notifiable->name.',')
            ->line('Your '.ucfirst($this->subscription->plan->value).' subscription ends on '.$this->subscription->current_period_end?->toFormattedDateString().'.');

        if ($this->subscription->billing_mode === 'manual' && $this->paymentUrl) {
            $mail->action('Pay for another 30 days', $this->paymentUrl);
        } else {
            $mail->line('Your saved payment method will be charged automatically before the end date.');
        }

        return $mail;
    }
}
