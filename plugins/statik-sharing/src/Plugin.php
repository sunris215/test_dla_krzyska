<?php

declare(strict_types=1);

namespace Statik\Sharing;

use Statik\Sharing\Dashboard\Dashboard;
use Statik\Sharing\Integration\Plugin\ElementorPluginIntegrator;
use Statik\Sharing\Integration\Plugin\GutenbergPluginIntegrator;
use Statik\Sharing\Integration\Plugin\JetEnginePluginIntegration;
use Statik\Sharing\Integration\Plugin\OffloadMediaPluginIntegrator;
use Statik\Sharing\Integration\Plugin\StatikGutenbergPluginIntegrator;
use Statik\Sharing\Post\AttachmentPostFilter;
use Statik\Sharing\Post\PostFilter;
use Statik\Sharing\Share\Handler\AjaxShareHandler;
use Statik\Sharing\Share\Handler\CronShareHandler;

/**
 * Class Plugin.
 */
class Plugin
{
    private string $path;

    /**
     * Plugin constructor.
     *
     * Run all plugin services and register in correct actions and run `init`
     * action when all plugin services are ready.
     */
    public function __construct(string $path)
    {
        $this->path = $path;

        \register_activation_hook($path, [$this, 'onActivation']);
        \register_deactivation_hook($path, [$this, 'onDeactivation']);

        \add_action('init', [$this, 'onInit'], 15);

        new AjaxShareHandler();
        new CronShareHandler();

        $this->initLanguages();

        /**
         * Fire plugin initialization action.
         */
        \do_action('Statik\Sharing\pluginInit');
    }

    /**
     * Init Languages support for plugin.
     */
    public function initLanguages(): void
    {
        \load_plugin_textdomain(
            'statik',
            false,
            \dirname(\plugin_basename($this->path)) . '/languages/'
        );
    }

    /**
     * Initialize Dashboard settings page and DeploymentLogger service.
     */
    public function onInit(): void
    {
        DI::IntegrationManager()
            ->registerIntegration(
                OffloadMediaPluginIntegrator::class,
                GutenbergPluginIntegrator::class,
                StatikGutenbergPluginIntegrator::class,
                ElementorPluginIntegrator::class,
                JetEnginePluginIntegration::class
            )
            ->initIntegrations();

        new PostFilter();
        new AttachmentPostFilter();

        new Dashboard();

        DI::Generator()->initializeFields();
    }

    /**
     * Check on plugin activation if the plugin is run on least 7.4 PHP
     * version and if successfully created a custom database table.
     */
    public function onActivation(): void
    {
        if (\PHP_VERSION_ID < 70400) {
            \deactivate_plugins(\plugin_basename($this->path));
            \wp_die(\__('The plugin requires at least PHP 7.4', 'statik'));
        }

        if (false === \is_multisite()) {
            \deactivate_plugins(\plugin_basename($this->path));
            \wp_die(\__('The plugin can be turned on only in Multisite instance!', 'statik'));
        }

        /**
         * Fire plugin activate action.
         */
        \do_action('Statik\Sharing\pluginActivate');
    }

    /**
     * Drop custom database table on plugin deactivation.
     */
    public function onDeactivation(): void
    {
        /**
         * Fire plugin deactivate action.
         */
        \do_action('Statik\Sharing\pluginDeactivate');
    }
}
