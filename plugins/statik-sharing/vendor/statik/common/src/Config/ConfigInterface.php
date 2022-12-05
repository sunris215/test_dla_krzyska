<?php

declare(strict_types=1);

namespace Statik\Common\Config;

/**
 * Interface ConfigInterface.
 */
interface ConfigInterface
{
    /**
     * Check if Settings key exists in constant with default settings.
     */
    public static function isDefaultSettings(?string $offset): bool;

    /**
     * Check get Array with default settings.
     */
    public static function getDefaultSettings(): array;

    /**
     * Save all Config in the database using the Driver mechanism.
     */
    public function save(): bool;

    /**
     * Get value from Config using a key.
     *
     * @param mixed|null $default
     */
    public function get(?string $offset, $default = null);

    /**
     * Get keys value from Config using a key.
     */
    public function getKeys(?string $offset): ?array;

    /**
     * Set value in Config by key.
     *
     * @param mixed $value
     */
    public function set(string $offset, $value): bool;

    /**
     * Check if value exists in Config.
     */
    public function has(string $offset): bool;

    /**
     * Delete value from Config.
     */
    public function delete(string $offset): bool;

    /**
     * Return the last element in an array passing a given truth test.
     *
     * @param mixed|null $default
     */
    public function last(string $offset, $default = null);

    /**
     * Prepend an item in config tree.
     *
     * @param mixed $value
     */
    public function prepend(string $offset, $value, bool $unique = false): bool;

    /**
     * Return all Config as a array.
     */
    public function toArray(bool $flatten = false): array;
}
