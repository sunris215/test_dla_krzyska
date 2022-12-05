<?php

declare(strict_types=1);

namespace Statik\Sharing\Share\Handler;

use Statik\Sharing\Share\Exception\ShareProcessException;
use Statik\Sharing\Share\ShareManager;

/**
 * Class AjaxShareHandler.
 */
class AjaxShareHandler implements HandlerInterface
{
    public const AJAX_IN_PROGRESS = 'statik_ajax_in_progress';

    /**
     * ShareManager constructor.
     */
    public function __construct()
    {
        \add_action('wp_ajax_statik_sharing', [$this, 'handleRequest']);
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(...$args): bool
    {
        $toSharePostId = (int) \filter_input(\INPUT_POST, 'post_id', \FILTER_SANITIZE_NUMBER_INT);
        $toShareBlogId = (int) \filter_input(\INPUT_POST, 'blog_id', \FILTER_SANITIZE_NUMBER_INT);
        $currentPostId = (int) \filter_input(\INPUT_POST, 'current_post_id', \FILTER_SANITIZE_NUMBER_INT);
        $sharingAction = (string) \filter_input(\INPUT_POST, 'sharing_action', \FILTER_SANITIZE_STRING);

        try {
            if (false === \current_user_can_for_blog($toShareBlogId, 'edit_posts')) {
                throw new ShareProcessException(
                    \__('Current user does not have permissions to share to this blog!', 'statik')
                );
            }

            if (false === \wp_verify_nonce($_POST['nonce'] ?? null, 'statik_sharing_meta_box_nonce')) {
                throw new ShareProcessException(
                    \__('Invalid request nonce. Please reload page and try again!', 'statik')
                );
            }

            $shareManager = new ShareManager(\get_current_blog_id(), $currentPostId, $toShareBlogId, $toSharePostId);

            switch ($sharingAction) {
                case 'delete':
                    $shareProcessor = $shareManager->deletePost();
                    break;
                case 'detach':
                    $shareProcessor = $shareManager->detachPost();
                    break;
                case 'share':
                default:
                    $shareProcessor = $shareManager->sharePost();
            }
        } catch (ShareProcessException $e) {
            \wp_send_json_error(['error' => ['message' => $e->getMessage(), 'code' => $e->getCode()]]);

            return false;
        }

        if ('share' === $sharingAction) {
            /**
             * Fire edit post link filter.
             *
             * @param string|null edit link
             * @param self        ShareProcessor instance
             */
            $editPostLink = \apply_filters('Statik\Sharing\editPostLink', null, $shareProcessor);
        }

        \wp_send_json_success([
            'blog_id'   => $shareProcessor->getDestinationBlogId() ?? null,
            'post_id'   => $shareProcessor->getDestinationObject()->getPost()->ID ?? null,
            'edit_link' => $editPostLink ?? null,
        ]);

        return true;
    }
}
