<?php

declare(strict_types=1);

namespace Statik\Sharing;

use Pimple\Container;
use Statik\Common\Settings\Generator;
use Statik\Sharing\Integration\IntegrationManager;

/**
 * Class DI.
 */
class DI
{
    /**
     * Get object from DI container.
     *
     * @return mixed
     */
    public static function get(string $key)
    {
        return $GLOBALS[DIProvider::CONTAINER_NAME]->offsetGet($key);
    }

    /**
     * Dependency Injection container.
     *
     * @return mixed
     */
    public static function container(): Container
    {
        return $GLOBALS[DIProvider::CONTAINER_NAME];
    }

    /**
     * Get Config object.
     */
    public static function Config(): Config
    {
        return static::get('config');
    }

    /**
     * Get Generator object.
     */
    public static function Generator(): Generator
    {
        return static::get('generator');
    }

    /**
     * Get Integration Manger object.
     */
    public static function IntegrationManager(): IntegrationManager
    {
        return static::get('integration');
    }

    /**
     * Get Integration Logger object.
     */
    public static function Logger(): Logger
    {
        return static::get('logger');
    }
}
