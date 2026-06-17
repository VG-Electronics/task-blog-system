<?php

namespace App\Services\Config;

abstract class ConfigService
{
    protected static string $configKey = '';

    protected function getConfigValue(string $key, mixed $default): mixed
    {
        if (static::$configKey === '') {
            throw new \RuntimeException('Config key is not set in ' . static::class);
        }

        return config(static::$configKey . '.' . $key, $default);
    }
}
