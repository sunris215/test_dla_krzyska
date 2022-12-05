<?php

declare(strict_types=1);

namespace Statik\Sharing\Post;

use Statik\Sharing\Helper\SwitcherTrait;

/**
 * Class PostMeta.
 */
class PostMeta
{
    use SwitcherTrait;

    public const META_KEY = 'statik_sharing_on_blogs';

    public const META_TYPE = 'statik_sharing_type';

    private int $postId;

    private ?string $postSharingType = null;

    private int $blogId;

    /**
     * MetaManager constructor.
     */
    public function __construct(int $postId = null, int $blogId = null)
    {
        $this->postId = (int) $postId ?: \get_the_ID();
        $this->blogId = (int) $blogId ?: \get_current_blog_id();
    }

    /**
     * Get sharing meta for Post.
     */
    public function getMeta(): object
    {
        return (object) self::safeSwitchToBlog(
            $this->blogId,
            'get_post_meta',
            $this->postId,
            static::META_KEY,
            true
        );
    }

    /**
     * Update Post sharing meta.
     */
    public function updateMeta(int $metaBlogId, int $metaPostId = null, string $metaStatus = null): bool
    {
        if ('primary' === $this->getSharingType()) {
            $meta = $this->getMeta();

            if (null === $metaPostId && isset($meta->{$metaBlogId})) {
                $meta->{$metaBlogId}->status = $metaStatus;
            } else {
                $meta->{$metaBlogId} = (object) [
                    'postId' => $metaPostId,
                    'status' => $metaStatus,
                ];
            }
        }

        if ('replica' === $this->getSharingType()) {
            $meta = [
                'blogId' => $metaBlogId,
                'postId' => $metaPostId,
            ];
        }

        return (bool) self::safeSwitchToBlog(
            $this->blogId,
            'update_post_meta',
            $this->postId,
            static::META_KEY,
            (object) ($meta ?? [])
        );
    }

    /**
     * Delete Post sharing meta.
     */
    public function deleteMeta(int $metaBlogId = null): bool
    {
        if (null !== $metaBlogId && $this->isSharingTypePrimary()) {
            $meta = $this->getMeta();
            unset($meta->{$metaBlogId});

            return (bool) self::safeSwitchToBlog(
                $this->blogId,
                'update_post_meta',
                $this->postId,
                static::META_KEY,
                (object) ($meta ?? [])
            );
        }

        return (bool) self::safeSwitchToBlog(
            $this->blogId,
            'delete_post_meta',
            $this->postId,
            static::META_KEY
        );
    }

    /**
     * Get Post sharing type.
     */
    public function getSharingType(): string
    {
        if (null === $this->postSharingType) {
            $this->postSharingType = (string) self::safeSwitchToBlog(
                $this->blogId,
                'get_post_meta',
                $this->postId,
                static::META_TYPE,
                true
            );
        }

        return $this->postSharingType;
    }

    /**
     * Update Post sharing type.
     */
    public function updateSharingType(string $type): bool
    {
        $result = (bool) self::safeSwitchToBlog(
            $this->blogId,
            'update_post_meta',
            $this->postId,
            static::META_TYPE,
            $type
        );

        $this->postSharingType = $type;

        return (bool) $result;
    }

    /**
     * Update Post sharing type.
     */
    public function deleteSharingType(): bool
    {
        return (bool) self::safeSwitchToBlog(
            $this->blogId,
            'delete_post_meta',
            $this->postId,
            static::META_TYPE
        );
    }

    /**
     * Terminate if Post sharing type is `primary`.
     */
    public function isSharingTypePrimary(): bool
    {
        return 'primary' === $this->getSharingType();
    }

    /**
     * Terminate if Post sharing type is `replica`.
     */
    public function isSharingTypeReplica(): bool
    {
        return 'replica' === $this->getSharingType();
    }
}
