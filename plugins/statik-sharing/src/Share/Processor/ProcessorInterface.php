<?php

declare(strict_types=1);

namespace Statik\Sharing\Share\Processor;

/**
 * Interface ProcessorInterface.
 */
interface ProcessorInterface
{
    /**
     * Share from the source blog into the destination blog.
     */
    public function share(): bool;

    /**
     * Delete from the destination blog.
     */
    public function delete(): bool;

    /**
     * Detach the source blog and the destination blog.
     */
    public function detach(): bool;
}
