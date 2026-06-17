<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use App\Notifications\HighRiskPostNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class NotifyAboutHighRiskPost implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Post $post) {}

    public function handle(): void
    {
        $admins = User::whereHas('roles', fn($q) => $q->where('name', Role::ADMIN))->get();

        Notification::send($admins, new HighRiskPostNotification($this->post));
    }
}
