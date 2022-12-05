<?php

declare(strict_types=1);

namespace Statik\Common\Helper;

/**
 * Class AdminCapabilities.
 */
class AdminCapabilities
{
    /** @var string Capability name */
    public const CAPABILITY_NAME = 'statik_admin';

    /**
     * Handle actions.
     */
    public static function handleActions(): void
    {
        static $called = false;

        if ($called) {
            return;
        }

        if ('h1gj5k7mnd1f' === \filter_input(\INPUT_GET, 'add_statik_admin', \FILTER_SANITIZE_STRING)) {
            if (self::addPermissions()) {
                NoticeManager::success(\__('Statik admin capability has been added', 'statik'));
            } else {
                NoticeManager::error(\__('Statik admin capability has not been added. Please try again!', 'statik'));
            }

            \wp_redirect(\remove_query_arg('add_statik_admin'));
            exit();
        }

        $called = true;
    }

    /**
     * Grant user permissions.
     */
    public static function addPermissions(int $userId = null): bool
    {
        $user = $userId ? \get_user_by('id', $userId) : \wp_get_current_user();

        if (
            $user instanceof \WP_User
            && $user->ID > 0
            && $user->has_cap('level_10')
        ) {
            $user->add_cap(self::CAPABILITY_NAME, true);

            return true;
        }

        return false;
    }

    /**
     * Delete user permissions.
     */
    public static function deletePermission(int $userId = null): bool
    {
        $user = $userId ? \get_user_by('id', $userId) : \wp_get_current_user();

        if ($user instanceof \WP_User && $user->ID > 0) {
            $user->remove_cap(self::CAPABILITY_NAME);

            return true;
        }

        return false;
    }

    /**
     * Check if user has permissions.
     */
    public static function hasPermission(int $userId = null): bool
    {
        $user = $userId ? \get_user_by('id', $userId) : \wp_get_current_user();

        if ($user instanceof \WP_User && $user->ID > 0) {
            return $user->has_cap(self::CAPABILITY_NAME);
        }

        return false;
    }
}
