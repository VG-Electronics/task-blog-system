<?php

namespace App\Contracts;

interface MailableNotification
{
    public function toMailSubject(object $notifiable): string;

    public function toMailBody(object $notifiable): string;
}
