<?php

declare(strict_types=1);

namespace Statik\Sharing\Share;

use Statik\Sharing\Helper\SwitcherTrait;
use Statik\Sharing\Post\PostMeta;

/**
 * Class ShareStructureManager.
 */
class ShareStructureManager
{
    use SwitcherTrait;

    /**
     * Get primary edit link.
     *
     * @see get_edit_post_link()
     */
    public static function getPrimaryPostEditLink(int $postId = null): ?string
    {
        if (null === $postId) {
            global $post;

            $postId = (int) $post->ID;
        }

        $postMetaManager = new PostMeta($postId);

        if ($postMetaManager->isSharingTypeReplica()) {
            $postMeta = $postMetaManager->getMeta();

            return self::safeSwitchToBlog(
                (int) $postMeta->blogId,
                'get_edit_post_link',
                $postMeta->postId,
                'edit'
            );
        }

        return \get_edit_post_link($postId);
    }

    /**
     * Get Post sharing structure.
     */
    public static function getPostSharingStructure(int $postId = null): array
    {
        if (null === $postId) {
            global $post;

            $postId = $post->ID;
        }

        $postMetaManager = new PostMeta($postId);
        $postMeta = $postMetaManager->getMeta();

        $currentBlogId = \get_current_blog_id();
        $metaPrimaryPrepared = [];
        $primaryBlogId = null;

        if ($postMetaManager->isSharingTypeReplica()) {
            self::safeSwitchToBlog((int) $postMeta->blogId);

            $postMetaManager = new PostMeta((int) $postMeta->postId);
            $postMeta = $postMetaManager->getMeta();
        }

        if ($postMetaManager->isSharingTypeReplica() || $postMetaManager->isSharingTypePrimary()) {
            foreach ($postMeta as $blogId => $postData) {
                if (empty($postData) || false === \is_object($postData) || empty($postData->postId)) {
                    continue;
                }

                self::safeSwitchToBlog((int) $blogId);
                $sharingType = (new PostMeta($postData->postId))->getSharingType();
                $primaryBlogId = $primaryBlogId ?: ('primary' === $sharingType ? $blogId : null);

                $metaPrimaryPrepared[$blogId] = (object) [
                    'blog'     => \get_site($blogId),
                    'post'     => \get_post($postData->postId),
                    'editUrl'  => \get_edit_post_link($postData->postId),
                    'frontUrl' => \get_permalink($postData->postId),
                    'type'     => $sharingType,
                    'status'   => $postData->status ?? 'shared',
                ];
            }
        }

        self::safeSwitchToBlog($currentBlogId);

        \ksort($metaPrimaryPrepared);

        return [
            'primaryBlogId' => $primaryBlogId,
            'blogs'         => $metaPrimaryPrepared,
        ];
    }
}
