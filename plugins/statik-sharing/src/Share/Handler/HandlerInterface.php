<?php

declare(strict_types=1);

namespace Statik\Sharing\Share\Handler;

/**
 * Interface HandlerInterface.
 */
interface HandlerInterface
{
    /**
     * Run action that handle request.
     *
     * @param mixed ...$args
     */
    public function handleRequest(...$args): bool;
}
