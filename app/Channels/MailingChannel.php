<?php

namespace App\Channels;

use App\Contracts\MailableNotification;
use App\Contracts\MailingServiceInterface;
use App\Models\User;

class MailingChannel
{
    public function __construct(private MailingServiceInterface $mailer) {}

    public function send(User $notifiable, MailableNotification $notification): void
    {
        $this->mailer->send(
            $notifiable,
            $notification->toMailSubject($notifiable),
            $notification->toMailBody($notifiable),
        );
    }
}
