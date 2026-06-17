<?php

namespace App\Services\Config;

class PostArchivingConfig extends ConfigService
{
    protected static string $configKey = 'post_archiving';

    const string ARCHIVE_AFTER_DAYS = 'archive_after_days';

    public function getArchiveAfterDays(): int
    {
        return $this->getConfigValue(self::ARCHIVE_AFTER_DAYS, 30);
    }
}
