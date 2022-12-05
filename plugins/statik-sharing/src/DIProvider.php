<?php

declare(strict_types=1);

namespace Statik\Sharing;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Statik\Common\Config\Driver\NetworkDatabaseDriver;
use Statik\Common\Settings\Generator;
use Statik\Sharing\Integration\IntegrationManager;

/**
 * Class DIProvider.
 */
class DIProvider implements ServiceProviderInterface
{
    public const CONTAINER_NAME = 'statik_sharing_di';

    protected static bool $isRegistered = false;

    /**
     * Register global DI container.
     */
    public static function registerGlobalDI(): void
    {
        if (false === static::$isRegistered) {
            $GLOBALS[static::CONTAINER_NAME] = new Container();
            $GLOBALS[static::CONTAINER_NAME]->register(new self());
        }
    }

    /**
     * Register Classes in the DI container.
     */
    public function register(Container $pimple): void
    {
        if (static::$isRegistered) {
            return;
        }

        $pimple['config'] = Config::Instance(
            'statik_sharing_settings',
            new NetworkDatabaseDriver('statik_sharing_settings')
        );

        $pimple['generator'] = new Generator($pimple['config'], 'statik_sharing');

        $pimple['integration'] = new IntegrationManager();

        $pimple['logger'] = new Logger('statik_sharing');

        static::$isRegistered = true;
    }
}
