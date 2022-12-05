<?php

declare(strict_types=1);

namespace Statik\Common\Config\Driver;

/**
 * Interface DriverInterface.
 *
 * Contains all methods that Driver require to have.
 */
interface DriverInterface
{
    /**
     * That method get data from Driver source and save it in the config variable.
     */
    public function getFromSource(): array;

    /**
     * Save all Config in the database using the WP mechanism.
     */
    public function setInSource(array $data): bool;
}
