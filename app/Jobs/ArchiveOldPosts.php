<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\Config\PostArchivingConfig;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class ArchiveOldPosts implements ShouldQueue
{
    use Queueable;

    public function __construct(private PostArchivingConfig $config) {}

    public function handle(): void
    {
        $days = $this->config->getArchiveAfterDays();

        Post::query()
            ->whereNull('archived_at')
            ->where('created_at', '<=', Carbon::now()->subDays($days))
            ->update(['archived_at' => Carbon::now()]);
    }
}
