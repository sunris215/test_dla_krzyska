<?php

declare(strict_types=1);

namespace Statik\Common\Config\Driver;

/**
 * Class NetworkDatabaseDriver.
 *
 * Driver using the WordPress database as a source.
 */
class NetworkDatabaseDriver extends AbstractDriver
{
    /**
     * {@inheritdoc}
     */
    public function setInSource(array $data): bool
    {
        return (bool) \update_site_option($this->namespace, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getFromSource(): array
    {
        return (array) \get_site_option($this->namespace, []);
    }
}
