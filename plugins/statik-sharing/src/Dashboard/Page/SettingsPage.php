<?php

declare(strict_types=1);

namespace Statik\Sharing\Dashboard\Page;

use Statik\Common\Dashboard\DashboardInterface;
use Statik\Common\Dashboard\Page\AbstractPage;
use Statik\Sharing\DI;
use Statik\Sharing\Helper\CustomPostType;
use const Statik\Sharing\PLUGIN_DIR;

/**
 * Class SettingsPage.
 */
class SettingsPage extends AbstractPage
{
    /**
     * SettingsPage constructor.
     */
    public function __construct(DashboardInterface $dashboard)
    {
        parent::__construct($dashboard);

        \add_filter('Statik\Common\networkSettingsTabs', [$this, 'addSettingsTab'], 10);
        \add_action('Statik\Common\networkSettingsPageTabs', [$this, 'getSettingsPage'], 10, 2);
    }

    /**
     * Add new tab to the plugins Settings page.
     */
    public function addSettingsTab(array $tabs): array
    {
        return \array_merge($tabs, ['sharing' => 'Sharing settings']);
    }

    /**
     * {@inheritdoc}
     */
    public function initPage(): void
    {
    }

    /**
     * Get settings page and set required variables.
     */
    public function getSettingsPage(string $currentTab): void
    {
        if ('sharing' === $currentTab) {
            include PLUGIN_DIR . 'src/Partials/SettingsPage.php';
        }
    }

    /**
     * Get custom settings for environment.
     */
    public static function getSettingsFields(): void
    {
        DI::Generator()->registerFields(
            'sharing_settings',
            [
                'settings.cpt'         => [
                    'type'    => 'input:checkbox',
                    'label'   => \__('Supported custom post types', 'statik'),
                    'values'  => CustomPostType::getAllCPTs(),
                    'default' => ['post', 'page'],
                    'attrs'   => [
                        'class' => 'regular-text',
                    ],
                ],
                'settings.attachments' => [
                    'type'   => 'input:checkbox',
                    'label'  => \__('Synchronize media', 'statik'),
                    'values' => [\__('Keep all media synchronize between blogs', 'statik')],
                    'attrs'  => [
                        'class' => 'regular-text',
                    ],
                ],
                'settings.cron'        => [
                    'type'        => 'input:checkbox',
                    'label'       => \__('Cron', 'statik'),
                    'description' => \__('Could cause a delay in access to shared content', 'statik'),
                    'values'      => [\__('Use cron tasks to share (experimental)', 'statik')],
                    'attrs'       => [
                        'class' => 'regular-text',
                    ],
                ],
            ]
        );
    }
}
