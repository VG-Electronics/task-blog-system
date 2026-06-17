<?php

namespace App\Contracts;

use App\Models\User;

interface MailingServiceInterface
{
    public function send(User $recipient, string $subject, string $body): void;
}
