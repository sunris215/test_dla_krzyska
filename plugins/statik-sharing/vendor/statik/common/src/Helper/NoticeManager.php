<?php

declare(strict_types=1);

namespace Statik\Common\Helper;

/**
 * Class NoticeManager.
 */
class NoticeManager
{
    private const VALID_TYPES = ['error', 'warning', 'success', 'info'];

    private const META_NAME = 'statik_notice';

    /**
     * Get rendered (HTML) notices for current user.
     */
    public static function render(): ?string
    {
        $notices = '';

        foreach (self::getArray() as $notice) {
            $notices .= \sprintf('<div class="notice %1$s"><p>%2$s</p></div>', $notice[0], $notice[1]);
        }

        return $notices;
    }

    /**
     * Display rendered notices for current user.
     */
    public static function display(): void
    {
        echo self::render();
    }

    /**
     * Add admin error notice for current user.
     */
    public static function error(string $message, bool $dismissible = true): void
    {
        self::register('error', $message, $dismissible);
    }

    /**
     * Add admin warning notice for current user.
     */
    public static function warning(string $message, bool $dismissible = true): void
    {
        self::register('warning', $message, $dismissible);
    }

    /**
     * Add admin success notice for current user.
     */
    public static function success(string $message, bool $dismissible = true): void
    {
        self::register('success', $message, $dismissible);
    }

    /**
     * Add admin info notice for current user.
     */
    public static function info(string $message, bool $dismissible = true): void
    {
        self::register('info', $message, $dismissible);
    }

    /**
     * Add admin notice for current user.
     */
    private static function register(string $class, string $message, bool $dismissible = true): void
    {
        if (empty($message) || false === \in_array($class, self::VALID_TYPES, true)) {
            return;
        }

        $class = 'notice-' . $class;

        if ($dismissible) {
            $class .= ' is-dismissible';
        }

        \add_user_meta(\get_current_user_id(), self::META_NAME, [$class, $message], false);
    }

    /**
     * Get notices for current user.
     */
    private static function getArray(): array
    {
        $id = \get_current_user_id();
        $notices = \get_user_meta($id, self::META_NAME, false);
        \delete_user_meta($id, self::META_NAME);

        return $notices ?: [];
    }
}
