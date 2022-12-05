<?php

declare(strict_types=1);

namespace Statik\Sharing\Share\Processor\Post;

use Statik\Sharing\DI;
use Statik\Sharing\Helper\SwitcherTrait;
use Statik\Sharing\Post\PostMeta;
use Statik\Sharing\Share\Data\AbstractData;
use Statik\Sharing\Share\Exception\ShareProcessException;
use Statik\Sharing\Share\Processor\AbstractProcessor;

/**
 * Class PostProcessor.
 */
class PostProcessor extends AbstractPostProcessor
{
    use SwitcherTrait;

    /**
     * {@inheritdoc}
     *
     * @throws ShareProcessException
     */
    public function share(): bool
    {
        parent::share();

        $postMeta = $this->sourceObject->getMeta();

        self::switchToBlog($this->sourceBlogId, function (PostMeta $postMeta): void {
            $postMeta->updateSharingType('primary');
            $postMeta->updateMeta($this->sourceBlogId, $this->sourceObject->getPost()->ID, 'primary');
        }, $postMeta);

        self::switchToBlog($this->destinationBlogId, [$this, 'insertPostIntoDestinationBlog']);

        self::switchToBlog(
            $this->sourceBlogId,
            [$postMeta, 'updateMeta'],
            $this->destinationBlogId,
            $this->destinationObject->getPost()->ID,
            'shared'
        );

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @see PostFilter::handleTrashedPost()
     */
    public function delete(): bool
    {
        parent::delete();

        self::safeSwitchToBlog(
            $this->sourceBlogId,
            [$this->sourceObject->getMeta(), 'deleteMeta'],
            $this->destinationBlogId
        );

        if (null === $this->destinationObject) {
            throw new ShareProcessException(\__('Not connected post cannot be deleted', 'statik'));
        }

        self::safeSwitchToBlog(
            $this->destinationBlogId,
            'wp_delete_post',
            $this->destinationObject->getObject()->ID
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function detach(): bool
    {
        parent::detach();

        self::safeSwitchToBlog(
            $this->sourceBlogId,
            [$this->sourceObject->getMeta(), 'deleteMeta'],
            $this->destinationBlogId
        );

        if (null === $this->destinationObject) {
            throw new ShareProcessException(\__('Not connected post cannot be detached', 'statik'));
        }

        self::safeSwitchToBlog($this->destinationBlogId, function (): void {
            $postMeta = $this->destinationObject->getMeta();
            $postMeta->deleteMeta();
            $postMeta->deleteSharingType();
        });

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function validate(): void
    {
        parent::validate();

        if ('attachment' === $this->sourceObject->getPost()->post_type) {
            throw new ShareProcessException(\__('Post processor cannot be used to process attachments', 'statik'));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function insertPostIntoDestinationBlog(): AbstractData
    {
        parent::insertPostIntoDestinationBlog();
        $this->replaceInternalLinks();

        self::safeSwitchToBlog($this->destinationBlogId, function (): void {
            $postMeta = $this->destinationObject->getMeta();
            $postMeta->updateSharingType('replica');
            $postMeta->updateMeta($this->sourceBlogId, $this->sourceObject->getPost()->ID);
            $this->duplicateThumbnail();
        });

        return $this->destinationObject;
    }

    /**
     * Replace all internal links in Post content.
     */
    private function replaceInternalLinks(): void
    {
        $sourceBlogHome = \get_home_url($this->sourceBlogId);
        $destinationBlogHome = \get_home_url($this->destinationBlogId);

        \preg_match_all(
            '~<a.*?href="(?<URL>.*?)".*?>~',
            $this->destinationObject->getPost()->post_content,
            $links,
            \PREG_SET_ORDER,
            0
        );

        /**
         * Fire before replace internal links filter.
         *
         * @param string            post content
         * @param AbstractProcessor ShareProcessor instance
         */
        $this->destinationObject->getPost()->post_content = (string) \apply_filters(
            'Statik\Sharing\beforeReplaceInternalLinks',
            $this->destinationObject->getPost()->post_content,
            $this
        );

        foreach ($links as $link) {
            if (0 !== \strpos($link['URL'], $sourceBlogHome)) {
                continue;
            }

            $destinationLink = \str_replace($sourceBlogHome, $destinationBlogHome, $link['URL']);

            $this->destinationObject->getPost()->post_content = \str_replace(
                "href=\"{$link['URL']}\"",
                "href=\"{$destinationLink}\"",
                $this->destinationObject->getPost()->post_content
            );
        }

        \wp_update_post($this->destinationObject->getPost());
    }

    /**
     * Duplicate feature image to destination blog.
     */
    private function duplicateThumbnail(): void
    {
        if (false === $this->sourceObject->getThumbnail() instanceof \WP_Post) {
            return;
        }

        $findDuplicate = \get_posts([
            'post_type'   => 'attachment',
            'post_status' => 'inherit',
            'name'        => $this->sourceObject->getThumbnail()->post_name,
        ]);

        if (1 === \count($findDuplicate)) {
            $destinationThumbnailId = \reset($findDuplicate)->ID;
            \update_post_meta($this->destinationObject->getId(), '_thumbnail_id', $destinationThumbnailId);
        } else {
            DI::Logger()->warningShare(
                'replace_post_thumbnail',
                \sprintf(
                    \__('Post %s (ID: %s) does not exists on destination blog', 'statik'),
                    $this->sourceObject->getThumbnail()->post_title,
                    $this->sourceObject->getThumbnail()->ID
                ),
                $this
            );
        }
    }
}
