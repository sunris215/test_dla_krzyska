<?php

declare(strict_types=1);

namespace Statik\Sharing\Dashboard;

use Statik\Common\Dashboard\AbstractDashboard;
use Statik\Common\Helper\NoticeManager;
use Statik\Sharing\Dashboard\Page\HelpersPage;
use Statik\Sharing\Dashboard\Page\SettingsPage;
use Statik\Sharing\Dashboard\Table\PostsTable;
use const Statik\Sharing\DEVELOPMENT;
use Statik\Sharing\DI;
use const Statik\Sharing\PLUGIN_DIR;
use const Statik\Sharing\PLUGIN_URL;
use Statik\Sharing\Post\PostMeta;
use const Statik\Sharing\VERSION;

/**
 * Class Dashboard.
 */
class Dashboard extends AbstractDashboard
{
    /**
     * Dashboard constructor.
     */
    public function __construct()
    {
        parent::__construct(
            PLUGIN_URL . 'assets',
            PLUGIN_DIR . 'assets',
            true,
            true,
            true
        );

        $this->saveSettingsHandler();

        \add_action('admin_print_styles-post.php', [$this, 'enqueueEditorStyles']);
        \add_action('admin_print_styles-post-new.php', [$this, 'enqueueEditorStyles']);
        \add_action('admin_print_scripts-post.php', [$this, 'enqueueEditorScripts']);
        \add_action('admin_print_scripts-post-new.php', [$this, 'enqueueEditorScripts']);

        \add_action('admin_print_styles', [$this, 'enqueueDashboardStyles']);
        \add_action('admin_print_scripts', [$this, 'enqueueDashboardScripts']);

        $this->registerPage(SettingsPage::class);
        $this->registerPage(HelpersPage::class);

        new PostsTable();
        new MetaBox();
    }

    /**
     * Enqueue required Styles for dashboard.
     */
    public function enqueueDashboardStyles(): void
    {
        $useDevelopFile = DEVELOPMENT && \file_exists("{$this->assetsDir}/stylesheets/settings.css");
        $useDevelopFileTable = DEVELOPMENT && \file_exists("{$this->assetsDir}/stylesheets/table.css");

        \wp_enqueue_style(
            'statik_sharing_settings',
            "{$this->assetsUrl}/stylesheets/settings" . ($useDevelopFile ? '.css' : '.min.css'),
            [],
            DEVELOPMENT ? \mt_rand() : VERSION
        );

        \wp_enqueue_style(
            'statik_sharing_table',
            "{$this->assetsUrl}/stylesheets/table" . ($useDevelopFileTable ? '.css' : '.min.css'),
            [],
            DEVELOPMENT ? \mt_rand() : VERSION
        );
    }

    /**
     * Enqueue required JavaScript scripts for dashboard.
     */
    public function enqueueDashboardScripts(): void
    {
        $useDevelopFile = DEVELOPMENT && \file_exists("{$this->assetsDir}/javascripts/table.js");

        \wp_enqueue_script(
            'statik_sharing_table',
            "{$this->assetsUrl}/javascripts/table" . ($useDevelopFile ? '.js' : '.min.js'),
            ['jquery', 'wp-polyfill'],
            DEVELOPMENT ? \mt_rand() : VERSION,
            true
        );
    }

    /**
     * Enqueue required Styles for editor.
     */
    public function enqueueEditorStyles(): void
    {
        global $post_type;

        if (false === \in_array($post_type, DI::Config()->get('settings.cpt.value', []), true)) {
            return;
        }

        $useDevelopFile = DEVELOPMENT && \file_exists(PLUGIN_DIR . 'assets/stylesheets/editor.css');

        \wp_enqueue_style(
            'statik_sharing_editor',
            PLUGIN_URL . 'assets/stylesheets/editor' . ($useDevelopFile ? '.css' : '.min.css'),
            [],
            DEVELOPMENT ? \mt_rand() : VERSION
        );
    }

    /**
     * Enqueue required JavaScript scripts for editor.
     */
    public function enqueueEditorScripts(): void
    {
        global $post_type;

        if (false === \in_array($post_type, DI::Config()->get('settings.cpt.value', []), true)) {
            return;
        }
        $useDevelopFile = DEVELOPMENT && \file_exists(PLUGIN_DIR . 'assets/javascripts/editor.js');

        \wp_enqueue_script(
            'statik_sharing_editor',
            PLUGIN_URL . 'assets/javascripts/editor' . ($useDevelopFile ? '.js' : '.min.js'),
            [],
            DEVELOPMENT ? \mt_rand() : VERSION,
            true
        );

        \ob_start();
        include_once PLUGIN_DIR . '/src/Partials/ReplicaModal.php';
        $replicaModal = \ob_get_clean();

        \wp_localize_script(
            'statik_sharing_editor',
            'statik_sharing_config',
            [
                'isReplica'           => (int) (new PostMeta())->isSharingTypeReplica(),
                'debug'               => (int) DEVELOPMENT,
                'replica'             => $replicaModal,
                'replicaCloseWarning' => \__(
                    'Are you sure you want to edit this page? After updating the Primary post, all changes will be overwritten!',
                    'statik'
                ),
            ]
        );
    }

    /**
     * This is the option save handler.
     */
    private function saveSettingsHandler(): void
    {
        if (empty($_POST['statik_sharing'])) {
            return;
        }

        if (false === \wp_verify_nonce($_POST['_wpnonce'] ?? null, 'statik_sharing_settings_nonce')) {
            NoticeManager::error(\__('Your settings have not been updated. Please try again!', 'statik'));

            return;
        }

        foreach ($_POST['statik_sharing'] as $key => $value) {
            DI::Config()->set($key, \stripslashes_deep($value));
        }

        DI::Config()->save();

        NoticeManager::success(\__('Your settings have been updated.', 'statik'));
    }
}
