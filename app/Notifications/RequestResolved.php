<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\RequestStatus;
use App\Models\TitleRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestResolved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public TitleRequest $titleRequest) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = $this->titleRequest->title_text;
        $status = $this->titleRequest->status;

        $mail = (new MailMessage)
            ->subject("Your title request for \"{$title}\" has been resolved")
            ->greeting('Hello '.$notifiable->name.',');

        if ($status === RequestStatus::Added) {
            $mail->line("Great news! Your requested title \"{$title}\" has been added to our catalog.");
            if ($this->titleRequest->resolvedTitle) {
                $mail->action('View Title', url('/titles/'.$this->titleRequest->resolvedTitle->slug));
            }
        } elseif ($status === RequestStatus::Approved) {
            $mail->line("Your request for \"{$title}\" has been approved and is being added to our catalog.");
        } elseif ($status === RequestStatus::Rejected) {
            $mail->line("Your request for \"{$title}\" could not be fulfilled.");
            if ($this->titleRequest->admin_note) {
                $mail->line("Note from reviewer: {$this->titleRequest->admin_note}");
            }
        } else {
            $mail->line("Your request for \"{$title}\" is now marked as {$status->value}.");
        }

        return $mail->salutation('');
    }
}
