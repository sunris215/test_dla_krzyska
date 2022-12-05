<?php

declare(strict_types=1);

namespace Statik\Common\Settings;

use Statik\Common\Config\ConfigInterface;

/**
 * Interface GeneratorInterface.
 */
interface GeneratorInterface
{
    /**
     * Generate HTML structure.
     */
    public function generateStructure(string $group): string;

    /**
     * Get namespace.
     */
    public function getNamespace(): string;

    /**
     * Get Config instance.
     */
    public function getConfig(): ConfigInterface;

    /**
     * Initialize fields with values.
     */
    public function registerFields(string $key, array $fields): ?array;
}
