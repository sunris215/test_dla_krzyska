<?php

declare(strict_types=1);

namespace Statik\Common\Dashboard;

use Statik\Common\Config\ConfigInterface;
use Statik\Common\Dashboard\Cpt\CptInterface;
use Statik\Common\Dashboard\Page\DebugPage;
use Statik\Common\Dashboard\Page\NetworkSettingsPage;
use Statik\Common\Dashboard\Page\PageInterface;
use Statik\Common\Dashboard\Page\SettingsPage;
use Statik\Common\Helper\AdminCapabilities;
use Statik\Common\Helper\NoticeManager;

/**
 * Class AbstractDashboard.
 */
abstract class AbstractDashboard implements DashboardInterface
{
    protected ConfigInterface $config;

    /** @var PageInterface[] */
    protected array $registeredPages = [];

    /** @var CptInterface[] */
    protected array $registeredCpts = [];

    protected string $assetsUrl;

    protected string $assetsDir;

    protected bool $network;

    /** @var bool[] */
    private static array $mainPageRegistered = ['single' => false, 'network' => false];

    /** @var bool[] */
    private static array $settingsPageRegistered = ['single' => false, 'network' => false];

    /**
     * AbstractDashboard constructor.
     */
    public function __construct(
        string $assetsUrl,
        string $assetsDir,
        bool $registerPage = true,
        bool $registerSettings = true,
        bool $network = false
    ) {
        $this->assetsUrl = $assetsUrl;
        $this->assetsDir = $assetsDir;
        $this->network = $network;

        if ($registerPage && false === self::$mainPageRegistered[$network ? 'network' : 'single']) {
            \add_action($this->network ? 'network_admin_menu' : 'admin_menu', [$this, 'registerMainPage']);
            self::$mainPageRegistered[$network ? 'network' : 'single'] = true;
        }

        \add_action($this->network ? 'network_admin_menu' : 'admin_menu', [$this, 'menuCleanUp'], \PHP_INT_MAX);
        \add_action($this->network ? 'network_admin_notices' : 'admin_notices', [NoticeManager::class, 'display']);

        if ($registerSettings && false === self::$settingsPageRegistered[$network ? 'network' : 'single']) {
            $this->registerPage($this->network ? NetworkSettingsPage::class : SettingsPage::class);
            self::$mainPageRegistered[$network ? 'network' : 'single'] = true;
        }

        /**
         * Fire show debug page filter.
         *
         * @param bool show debug page
         */
        $showDebugPage = \apply_filters('Statik\Common\showDebugPage', true);

        AdminCapabilities::hasPermission() && $showDebugPage && $this->registerPage(DebugPage::class);
    }

    /**
     * Check if network dashboard.
     */
    public function isNetwork(): bool
    {
        return $this->network;
    }

    /**
     * {@inheritdoc}
     */
    public function registerPage(string $pageClassName): DashboardInterface
    {
        if (
            false === \array_key_exists($pageClassName, $this->registeredPages)
            && \is_a($pageClassName, PageInterface::class, true)
        ) {
            /** @var PageInterface $object */
            $object = new $pageClassName($this);

            if (\method_exists($object, 'getSettingsFields')) {
                $object::getSettingsFields();
            }

            $this->registeredPages[$pageClassName] = $object;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function registerCpt(string $cptClassName): DashboardInterface
    {
        if (
            false === \array_key_exists($cptClassName, $this->registeredCpts)
            && \is_a($cptClassName, CptInterface::class, true)
        ) {
            /** @var CptInterface $object */
            $object = new $cptClassName($this);

            $this->registeredCpts[$cptClassName] = $object;
        }

        return $this;
    }

    /**
     * Register default main page.
     */
    public function registerMainPage(): void
    {
        /**
         * Fire statik menu position filter.
         *
         * @param int current position
         */
        $position = \apply_filters('Statik\Common\menuPosition', 100);

        \add_menu_page(
            \__('Statik', 'statik'),
            \__('Statik', 'statik'),
            'manage_options',
            'statik',
            '__return_empty_string',
            "{$this->assetsUrl}/images/jam.svg",
            $position
        );
    }

    /**
     * Remove the menu item from the submenu.
     */
    public function menuCleanUp(): void
    {
        unset($GLOBALS['submenu']['statik'][0]);
    }
}
