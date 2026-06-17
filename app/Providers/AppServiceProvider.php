<?php

namespace App\Providers;

use App\Contracts\MailingServiceInterface;
use App\Services\LogMailingService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MailingServiceInterface::class, LogMailingService::class);
    }

    public function boot(): void
    {
        //
    }
}
