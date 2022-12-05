<?php

declare(strict_types=1);

namespace Statik\Sharing\Post;

use Statik\Sharing\DI;
use Statik\Sharing\Helper\SwitcherTrait;
use Statik\Sharing\Share\Exception\ShareProcessException;
use Statik\Sharing\Share\Handler\CronShareHandler;
use Statik\Sharing\Share\ShareManager;

/**
 * Class AttachmentPostFilter.
 */
class AttachmentPostFilter
{
    use SwitcherTrait;

    private bool $isAttachmentSync;

    private bool $isCronUpload;

    private bool $isCron;

    /**
     * PostFilter constructor.
     */
    public function __construct()
    {
        $this->isAttachmentSync = 0 === DI::Config()->get('settings.attachments.value.0');
        $this->isCronUpload = 0 === DI::Config()->get('settings.cron.value.0');
        $this->isCron = \wp_doing_cron();

        \add_action('sanitize_file_name', [$this, 'sanitizeMediaFileName']);
        \add_action('wp_insert_attachment_data', [$this, 'addUniqueSuffixForAttachment'], 10, 2);
        \add_action('post-upload-ui', [$this, 'showUploadTimeWarningMessage']);

        \add_filter('bulk_actions-upload', [$this, 'addMediaBulkActions']);
        \add_filter('media_row_actions', [$this, 'addMediaRowActions'], 10, 2);
        \add_action('attachment_submitbox_misc_actions', [$this, 'addMediaDeleteButton'], 300);

        \add_action('delete_attachment', [$this, 'deleteMediaFromAllBlogs']);

        \add_filter('upload_dir', [$this, 'removeBlogInfoFromUploads']);

        $this->handleMediaBulkActions();

        false === $this->isCron && \add_action('add_attachment', [$this, 'shareMediaAcrossAllBlogs']);
        false === $this->isCron && \add_action('attachment_updated', [$this, 'shareMediaAcrossAllBlogs']);
    }

    /**
     * Change all file names to lowercase.
     */
    public function sanitizeMediaFileName(string $fileName): string
    {
        return \strtolower($fileName);
    }

    /**
     * Add custom suffix for all Attachment for prevent duplicate slugs.
     */
    public function addUniqueSuffixForAttachment(array $post, array $unfilteredPost): array
    {
        if (false === (bool) \preg_match('/-media-[a-z0-9]{32}$/', $post['post_name'])) {
            $fileMd5 = $unfilteredPost['file'] ? \md5_file($unfilteredPost['file']) : \md5($post['post_modified']);
            $post['post_name'] .= '-media-' . \md5($fileMd5 . \time());
        }

        return $post;
    }

    /**
     * Display upload time warning message.
     */
    public function showUploadTimeWarningMessage(): void
    {
        if (false === $this->isAttachmentSync) {
            return;
        }

        echo \__('Statik Sharing keeps all attachments synchronize between blogs.', 'statik') . '<br>';

        if ($this->isCronUpload) {
            echo \__(
                    'Attachments are shared using Cron task, so attachments on the other blocks will be accessible after a few minutes.',
                    'statik'
                ) . '<br>';

            return;
        }

        echo \__(
                'That could have an impact on the performance of upload process and take a few minutes more.',
                'statik'
            ) . '<br>';
    }

    /**
     * Share media across all blogs.
     */
    public function shareMediaAcrossAllBlogs(int $postId): void
    {
        if (false === $this->isAttachmentSync) {
            return;
        }

        if ((bool) \get_post_meta($postId, CronShareHandler::CRON_IN_PROGRESS, true)) {
            return;
        }

        $currentBlogId = \get_current_blog_id();
        $blogs = \get_sites([
            'site__not_in' => $currentBlogId,
            'archived'     => 0,
            'spam'         => 0,
            'deleted'      => 0,
        ]);

        \remove_filter('add_attachment', [$this, 'shareMediaAcrossAllBlogs']);
        \remove_filter('attachment_updated', [$this, 'shareMediaAcrossAllBlogs']);

        /**
         * Fire cron interval filter.
         *
         * @return int
         */
        $cronInterval = (int) \apply_filters('Statik\Sharing\cronInterval', 0);

        foreach ($blogs as $key => $blog) {
            /** @var \WP_Site $blog */
            $destinationPostId = $this->findBlogPostId($currentBlogId, $postId, (int) $blog->blog_id);

            if ($this->isCronUpload) {
                $time = \time();

                self::safeSwitchToBlog(
                    (int) $blog->blog_id,
                    'update_post_meta',
                    $destinationPostId,
                    CronShareHandler::CRON_IN_PROGRESS,
                    true
                );

                \wp_schedule_single_event(
                    $time + (($key + 1) * $cronInterval),
                    'Statik\Sharing\handleCronShare',
                    ['attachment', $currentBlogId, $postId, (int) $blog->blog_id, $destinationPostId]
                );
            } else {
                try {
                    $shareManager = new ShareManager($currentBlogId, $postId, (int) $blog->blog_id, $destinationPostId);
                    $shareManager->shareAttachment();
                } catch (ShareProcessException $e) {
                    continue;
                }
            }
        }

        \add_action('add_attachment', [$this, 'shareMediaAcrossAllBlogs']);
        \add_action('attachment_updated', [$this, 'shareMediaAcrossAllBlogs']);
    }

    /**
     * Add media bulk actions.
     */
    public function addMediaBulkActions(array $actions): array
    {
        if (false === $this->isAttachmentSync) {
            return $actions;
        }

        $actions['sharing-delete'] = \__('Delete permanently from all blogs', 'statik');

        return $actions;
    }

    /**
     * Add media delete button.
     */
    public function addMediaDeleteButton(?\WP_Post $post): void
    {
        if (false === $this->isAttachmentSync) {
            return;
        }

        if (false === \current_user_can('delete_post', $post->ID)) {
            return;
        }

        $notice = \esc_js('return !!confirm("' . \__('You are about to permanently delete these items from all blogs.'
                . "\nThis action cannot be undone.\n'Cancel' to stop, 'OK' to delete.", 'statik') . '");'); ?>

        <div class="misc-pub-section misc-pub-original-image">
            <a class="submitdelete deletion" onclick="<?= $notice; ?>"
               href="<?= \get_delete_post_link($post->ID, null, true); ?>&sharing-delete=1">
                <?= \__('Delete permanently from all blogs', 'statik'); ?>
            </a>
        </div>

        <?php
    }

    /**
     * Add media row actions.
     */
    public function addMediaRowActions(array $actions, \WP_Post $post): array
    {
        if (false === $this->isAttachmentSync) {
            return $actions;
        }

        if (false === \current_user_can('delete_post', $post->ID)) {
            return $actions;
        }

        return \array_slice($actions, 0, 2, true) +
            [
                'delete delete-all' => \sprintf(
                    '<a href="%s" class="submitdelete delete" onclick="%s">%s</a>',
                    \wp_nonce_url("post.php?action=delete&amp;post={$post->ID}&amp;sharing-delete=1",
                        'delete-post_' . $post->ID),
                    \esc_js('return !!confirm("' . \__('You are about to permanently delete these items from all blogs.'
                            . "\nThis action cannot be undone.\n'Cancel' to stop, 'OK' to delete.", 'statik') . '");'),
                    \__('Delete Permanently from all blogs', 'statik')
                ),
            ] +
            \array_slice($actions, 2, \count($actions) - 2, true);
    }

    /**
     * Handle media bulk actions.
     */
    private function handleMediaBulkActions(): void
    {
        if (false === $this->isAttachmentSync) {
            return;
        }

        if (false == isset($_REQUEST['action']) || 'sharing-delete' !== $_REQUEST['action']) {
            return;
        }

        $_REQUEST['sharing-delete'] = '1';

        foreach ($_REQUEST['media'] as $media) {
            $mediaId = \filter_var($media, \FILTER_VALIDATE_INT);
            \wp_delete_attachment($mediaId, true);
        }
    }

    /**
     * Delete media from all blogs.
     */
    public function deleteMediaFromAllBlogs(int $postId): void
    {
        if (false === $this->isAttachmentSync) {
            return;
        }

        if (false === isset($_REQUEST['sharing-delete']) || '1' !== $_REQUEST['sharing-delete']) {
            return;
        }

        \remove_action('delete_attachment', [$this, 'deleteMediaFromAllBlogs']);

        $currentBlogId = \get_current_blog_id();
        $blogs = \get_sites([
            'site__not_in' => $currentBlogId,
            'archived'     => 0,
            'spam'         => 0,
            'deleted'      => 0,
        ]);

        foreach ($blogs as $key => $blog) {
            /** @var \WP_Site $blog */
            $destinationPostId = $this->findBlogPostId($currentBlogId, $postId, (int) $blog->blog_id);

            if (null === $destinationPostId) {
                continue;
            }

            self::safeSwitchToBlog($blog->id, 'wp_delete_attachment', $destinationPostId);
        }

        \add_action('delete_attachment', [$this, 'deleteMediaFromAllBlogs']);
    }

    /**
     * Find post ID on the blog based on the source blog.
     */
    private function findBlogPostId(int $sourceBlogId, int $sourcePostId, int $destinationBlogId): ?int
    {
        /** @var \WP_Post $sourcePost */
        $sourcePost = self::safeSwitchToBlog($sourceBlogId, 'get_post', $sourcePostId);

        $foundDestinationPost = (array) self::safeSwitchToBlog($destinationBlogId, 'get_posts', [
            'post_type'      => 'any',
            'posts_per_page' => 1,
            'name'           => $sourcePost->post_name,
            'fields'         => 'ids',
            'post_status'    => ['inherit', 'publish'],
        ]);

        return (int) \reset($foundDestinationPost) ?: null;
    }

    /**
     * Remove blog info from uploads.
     */
    public function removeBlogInfoFromUploads(array $directories): array
    {
        $blogId = \get_current_blog_id();

        \array_walk($directories, function (&$directory) use ($blogId): void {
            if (false === \is_string($directory)) {
                return;
            }

            $directory = \str_replace(["/sites/{$blogId}/", "/sites/{$blogId}"], ['/', ''], $directory);
        });

        return $directories;
    }
}
