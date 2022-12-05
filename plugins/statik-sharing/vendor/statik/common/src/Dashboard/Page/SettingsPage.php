<?php

declare(strict_types=1);

namespace Statik\Common\Dashboard\Page;

use const Statik\Common\COMMON_DIR;
use Statik\Common\Dashboard\DashboardInterface;

/**
 * Class SettingsPage.
 */
class SettingsPage extends AbstractPage
{
    private static bool $registered = false;

    /**
     * DeploymentPage constructor.
     */
    public function __construct(DashboardInterface $dashboard)
    {
        parent::__construct($dashboard);

        if (self::$registered) {
            return;
        }

        \add_action($dashboard->isNetwork() ? 'network_admin_menu' : 'admin_menu', [$this, 'initPage'], 100);

        self::$registered = true;
    }

    /**
     * {@inheritdoc}
     */
    public function initPage(): void
    {
        /**
         * Fire settings page position filter.
         *
         * @param int current position
         */
        $position = \apply_filters('Statik\Common\settingsPagePosition', 5);

        \add_submenu_page(
            'statik',
            \__('Statik Settings', 'statik'),
            \__('Settings', 'statik'),
            'manage_options',
            'statik_settings',
            fn () => $this->getSettingsPage(),
            $position
        );
    }

    /**
     * Get settings page and set required variables.
     */
    public function getSettingsPage(): void
    {
        include COMMON_DIR . 'src/Partials/SettingsPage.php';
    }
}
