<?php

declare(strict_types=1);

namespace Statik\Common\Config\Driver;

/**
 * Class AbstractDriver.
 *
 * Class contains all methods required in Driver.
 */
abstract class AbstractDriver implements DriverInterface
{
    protected string $namespace;

    /**
     * AbstractDriver constructor.
     */
    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }
}
