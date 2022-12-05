<?php

declare(strict_types=1);

namespace Statik\Sharing;

use Illuminate\Support\Arr;
use Statik\Common\Config\AbstractConfig;

/**
 * Class Config.
 */
class Config extends AbstractConfig
{
    /**
     * {@inheritdoc}
     */
    public static function isDefaultSettings(?string $offset): bool
    {
        return USE_DEFAULT_SETTINGS && Arr::has(self::getDefaultSettings(), $offset);
    }

    /**
     * {@inheritdoc}
     */
    public static function getDefaultSettings(): array
    {
        return \is_array(DEFAULT_SETTINGS)
            ? (array) DEFAULT_SETTINGS
            : \json_decode((string) DEFAULT_SETTINGS, true);
    }
}
