<?php

declare(strict_types=1);

namespace Statik\Sharing\Dashboard\Table;

use Statik\Sharing\DI;
use Statik\Sharing\Post\PostMeta;
use Statik\Sharing\Share\Handler\AjaxShareHandler;
use Statik\Sharing\Share\Handler\CronShareHandler;
use Statik\Sharing\Share\ShareStructureManager;

/**
 * Class PostFilter.
 */
class PostsTable
{
    /**
     * PostFilter constructor.
     */
    public function __construct()
    {
        \add_filter('user_has_cap', [$this, 'checkUserCapsForEdit'], 10, 3);

        \add_filter('manage_posts_columns', [$this, 'addSharingColumn']);
        \add_filter('manage_pages_columns', [$this, 'addSharingColumn']);

        \add_action('manage_posts_custom_column', [$this, 'displayPostsSharingInfo'], 10, 2);
        \add_action('manage_pages_custom_column', [$this, 'displayPostsSharingInfo'], 10, 2);
    }

    /**
     * Filters the columns displayed in the Posts list table.
     */
    public function addSharingColumn(array $columns): array
    {
        $supportedCpt = DI::Config()->get('settings.cpt.value', []);

        if (false === \in_array($_GET['post_type'] ?? 'post', $supportedCpt)) {
            return $columns;
        }

        return \array_merge($columns, ['sharing' => \__('Sharing', 'statik')]);
    }

    /**
     * Display column for the sharing information.
     */
    public function displayPostsSharingInfo(string $columnName, int $postId): void
    {
        global $wpdb;

        if ('sharing' !== $columnName) {
            return;
        }

        $sharing = ShareStructureManager::getPostSharingStructure($postId);
        $postMeta = new PostMeta($postId);

        if (0 === \count($sharing)) {
            return;
        }

        if ($postMeta->isSharingTypePrimary()) {
            foreach ($sharing['blogs'] as $blogId => $blogData) {
                if ((int) $wpdb->blogid === $blogId) {
                    continue;
                }

                switch ($blogData->status) {
                    case AjaxShareHandler::AJAX_IN_PROGRESS:
                        $icon = '<span class="dashicons dashicons-warning"></span>';
                        break;
                    case CronShareHandler::CRON_IN_PROGRESS:
                        $icon = '<span class="dashicons dashicons-update-alt"></span>';
                        break;
                    default:
                        $icon = '<span class="dashicons dashicons-yes-alt"></span>';
                }

                \printf(
                    '<span id="blog-%s" class="statik-share-icon">%s %s</span></br>',
                    $blogId,
                    $blogData->blog->blogname,
                    $icon
                );
            }
        } elseif ($postMeta->isSharingTypeReplica()) {
            $primaryBlog = $sharing['blogs'][$sharing['primaryBlogId']] ?? null;
            \printf(
                '<a href="%s" class="button button-secondary">Edit primary post</button>',
                $primaryBlog->editUrl ?? null
            );
        }
    }

    /**
     * Check user capabilities for edit post if post is `replica`.
     */
    public function checkUserCapsForEdit(array $allCaps, array $requiredCaps, array $args)
    {
        if (isset($_GET['statik']) && 'update-sharing-replica' === $_GET['statik']) {
            return $allCaps;
        }

        if (\wp_doing_ajax()) {
            return $allCaps;
        }

        $postId = (int) ($args[2] ?? 0);

        if (0 === $postId) {
            return $allCaps;
        }

        if (\in_array('edit_post', $args) || \in_array('delete_post', $args)) {
            if ((new PostMeta($postId))->isSharingTypeReplica()) {
                return [];
            }
        }

        return $allCaps;
    }
}
