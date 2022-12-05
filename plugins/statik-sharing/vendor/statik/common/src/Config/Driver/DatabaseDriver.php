<?php

declare(strict_types=1);

namespace Statik\Common\Config\Driver;

/**
 * Class DatabaseDriver.
 *
 * Driver using the WordPress database as a source.
 */
class DatabaseDriver extends AbstractDriver
{
    /**
     * {@inheritdoc}
     */
    public function setInSource(array $data): bool
    {
        return (bool) \update_option($this->namespace, $data, false);
    }

    /**
     * {@inheritdoc}
     */
    public function getFromSource(): array
    {
        return (array) \get_option($this->namespace, []);
    }
}
