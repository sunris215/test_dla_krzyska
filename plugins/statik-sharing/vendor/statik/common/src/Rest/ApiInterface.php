<?php

declare(strict_types=1);

namespace Statik\Common\Rest;

/**
 * Interface ApiInterface.
 *
 * Interface contains all required methods in Api.
 */
interface ApiInterface
{
    /**
     * Get this version of API namespace.
     */
    public function getNamespace(): string;

    /**
     * Register single controller in API.
     */
    public function registerController(string $controllerName): self;
}
