<?php

declare(strict_types=1);

namespace Statik\Common\Rest\Controller;

/**
 * Interface ControllerInterface.
 */
interface ControllerInterface
{
    /**
     * Registers the routes for the objects of the controller.
     */
    public function registerRoutes(): void;
}
