<?php

declare(strict_types=1);

namespace Statik\Sharing\Integration;

/**
 * Interface IntegratorInterface.
 */
interface IntegratorInterface
{
    /**
     * Determinate rules when the integrator should be turned on.
     */
    public static function canBeEnabled(): bool;
}
