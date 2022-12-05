<?php

declare(strict_types=1);

namespace Statik\Sharing\Post;

use Statik\Sharing\DI;
use Statik\Sharing\Helper\SwitcherTrait;
use Statik\Sharing\Share\Exception\ShareProcessException;
use Statik\Sharing\Share\Handler\AjaxShareHandler;
use Statik\Sharing\Share\Handler\CronShareHandler;

/**
 * Class PostFilter.
 */
class PostFilter
{
    use SwitcherTrait;

    private bool $isCronUpload;

    /**
     * PostFilter constructor.
     */
    public function __construct()
    {
        $this->isCronUpload = 0 === DI::Config()->get('settings.cron.value.0');

        \add_action('trashed_post', [$this, 'removeSharingMetaFromRemovedPost']);
        \add_action('save_post', [$this, 'saveSharingMetaOnPostSave']);
    }

    /**
     * Remove all Sharing meta for removed posts.
     *
     * @throws ShareProcessException
     */
    public function removeSharingMetaFromRemovedPost(int $postId): void
    {
        $post = \get_post($postId);
        $postMeta = new PostMeta($postId);
        $blogId = \get_current_blog_id();

        if (false === $post instanceof \WP_Post) {
            return;
        }

        if ($postMeta->isSharingTypePrimary()) {
            foreach ($postMeta->getMeta() as $blogId => $postData) {
                if (empty($postData) || false === \is_object($postData) || empty($postData->postId)) {
                    continue;
                }

                self::switchToBlog(
                    (int) $blogId,
                    static function (object $postData): void {
                        $blogPostMeta = new PostMeta($postData->postId);
                        $blogPostMeta->deleteMeta();
                        $blogPostMeta->deleteSharingType();
                    },
                    $postData
                );
            }
        } elseif ($postMeta->isSharingTypeReplica()) {
            $meta = $postMeta->getMeta();

            self::switchToBlog(
                $meta->blogId,
                fn (object $meta, int $blogId) => (new PostMeta($meta->postId))->deleteMeta($blogId),
                $postMeta->getMeta(),
                $blogId
            );
        }

        $postMeta->deleteMeta();
        $postMeta->deleteSharingType();
    }

    /**
     * Update sharing posts statuses for share after reload page.
     */
    public function saveSharingMetaOnPostSave(int $postId): void
    {
        $sharingMeta = \filter_input_array(
            \INPUT_POST,
            [
                'statik_share_to' => [
                    'filter' => \FILTER_VALIDATE_INT,
                    'flags'  => \FILTER_REQUIRE_ARRAY,
                ],
            ]
        );
        $sharingMeta = $sharingMeta['statik_share_to'] ?? [];

        $postMeta = new PostMeta($postId);

        if (false === $postMeta->isSharingTypePrimary()) {
            return;
        }

        /**
         * Fire cron interval filter.
         *
         * @return int
         */
        $cronInterval = (int) \apply_filters('Statik\Sharing\cronInterval', 30);

        $currentBlogId = \get_current_blog_id();
        $time = \time();
        $key = 1;

        foreach ($postMeta->getMeta() as $blogId => $post) {
            if (
                ([] !== $sharingMeta && false === \in_array((int) $blogId, $sharingMeta, true))
                || 'primary' === $post->status
            ) {
                continue;
            }

            if ($this->isCronUpload) {
                \wp_schedule_single_event(
                    $time + (($key++) * $cronInterval),
                    'Statik\Sharing\handleCronShare',
                    ['post', $currentBlogId, $postId, (int) $blogId, $post->postId]
                );

                $postMeta->updateMeta((int) $blogId, null, CronShareHandler::CRON_IN_PROGRESS);
            } else {
                $postMeta->updateMeta((int) $blogId, null, AjaxShareHandler::AJAX_IN_PROGRESS);
            }
        }
    }
}
