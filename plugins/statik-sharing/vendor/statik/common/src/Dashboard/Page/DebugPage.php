<?php

declare(strict_types=1);

namespace Statik\Common\Dashboard\Page;

use const Statik\Common\COMMON_DIR;
use Statik\Common\Config\AbstractConfig;
use Statik\Common\Dashboard\DashboardInterface;
use Statik\Common\Helper\NoticeManager;

/**
 * Class DebugPage.
 */
class DebugPage extends AbstractPage
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

        $this->saveDebugActionsHandler();

        self::$registered = true;
    }

    /**
     * {@inheritdoc}
     */
    public function initPage(): void
    {
        /**
         * Fire network settings page position filter.
         *
         * @param int current position
         */
        $position = \apply_filters('Statik\Common\debugPagePosition', 5);

        \add_submenu_page(
            'statik',
            \__('Debug', 'statik'),
            \__('Debug', 'statik'),
            'manage_options',
            'statik_debug',
            fn () => $this->getSettingsPage(),
            $position
        );
    }

    /**
     * Get settings page and set required variables.
     */
    public function getSettingsPage(): void
    {
        include COMMON_DIR . 'src/Partials/DebugPage.php';
    }

    /**
     * This is the option save handler.
     */
    public function saveDebugActionsHandler(): void
    {
        if (empty($_POST['statik_debug'])) {
            return;
        }

        if (false === \wp_verify_nonce($_POST['_commons_nonce'] ?? null, 'statik_commons_debug')) {
            NoticeManager::error(\__('Your settings have not been updated. Please try again!', 'statik'));

            return;
        }

        $data = $_POST['statik_debug'];
        $config = \filter_var($data['config'] ?? '', \FILTER_SANITIZE_STRING);
        $configs = AbstractConfig::getInstances();

        if (false === isset($configs[$config])) {
            NoticeManager::error(\__('Your settings have not been updated. Please try again!', 'statik'));

            return;
        }

        if (isset($data['add'])) {
            $configs[$config]->set($data['key'], $data['value']);
        } else {
            $configs[$config]->delete($data['key']);
        }

        $configs[$config]->save();

        NoticeManager::success(\__('Your settings have been updated.', 'statik'));
    }
}
