<?php

namespace App\Services;

use App\Contracts\MailingServiceInterface;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class LogMailingService implements MailingServiceInterface
{
    public function send(User $recipient, string $subject, string $body): void
    {
        Log::info("Mail to {$recipient->email} | Subject: {$subject} | Body: {$body}");
    }
}
