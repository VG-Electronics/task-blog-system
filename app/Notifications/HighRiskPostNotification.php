<?php

namespace App\Notifications;

use App\Channels\MailingChannel;
use App\Contracts\MailableNotification;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class HighRiskPostNotification extends Notification implements ShouldQueue, MailableNotification
{
    use Queueable;

    public $queue = 'emails';

    public function __construct(public readonly Post $post) {}

    public function via(object $notifiable): array
    {
        return [MailingChannel::class];
    }

    public function toMailSubject(object $notifiable): string
    {
        return "High Risk Post Alert: #{$this->post->id}";
    }

    public function toMailBody(object $notifiable): string
    {
        return "Post #{$this->post->id} '{$this->post->title}' has been flagged as HIGH risk (score: {$this->post->risk_score}).";
    }
}
