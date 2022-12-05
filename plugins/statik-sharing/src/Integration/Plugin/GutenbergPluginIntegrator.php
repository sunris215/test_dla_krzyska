<?php

declare(strict_types=1);

namespace Statik\Sharing\Integration\Plugin;

use Statik\Sharing\Helper\SwitcherTrait;
use Statik\Sharing\Share\Data\AttachmentPostData;
use Statik\Sharing\Share\Exception\ShareProcessException;
use Statik\Sharing\Share\Processor\Post\AbstractPostProcessor;
use Statik\Sharing\Share\Processor\Post\AttachmentPostProcessor;

/**
 * Class GutenbergPluginIntegrator.
 */
class GutenbergPluginIntegrator extends AbstractPluginIntegrator
{
    use SwitcherTrait;

    private int $sourceBlogId;

    /**
     * GutenbergPluginIntegrator constructor.
     */
    public function __construct()
    {
        \add_filter('Statik\Sharing\excludedPostMetaKeys', [$this, 'addExcludedMetaKeys']);
        \add_filter('Statik\Sharing\beforeUpdatePost', [$this, 'filterPostContent'], 10, 3);
        \add_filter('Statik\Sharing\beforeInsertPost', [$this, 'filterPostContent'], 10, 3);
        \add_filter('Statik\Sharing\beforeReplaceInternalLinks', [$this, 'replaceInternalLinks'], 10, 3);
    }

    /**
     * {@inheritdoc}
     */
    public static function canBeEnabled(): bool
    {
        return true;
    }

    /**
     * Exclude plugin Post meta from sharing.
     */
    public function addExcludedMetaKeys(array $excludedMetaKeys): array
    {
        return \array_merge(['statik_gutenberg_blocks'], $excludedMetaKeys);
    }

    /**
     * Filter post content and look for any attachment blocks and rebuild them.
     */
    public function filterPostContent(array $post, AbstractPostProcessor $processor): array
    {
        $this->sourceBlogId = $processor->getSourceBlogId();

        \remove_action('Statik\Sharing\beforeUpdatePost', [$this, 'filterPostContent']);
        \remove_action('Statik\Sharing\beforeInsertPost', [$this, 'filterPostContent']);

        $parsedBlocks = \parse_blocks($post['post_content']);
        $rebuildBlocks = $this->parseBlocks($parsedBlocks);
        $post['post_content'] = \serialize_blocks($rebuildBlocks);
        $post['post_content'] = \str_replace('u00', '\\u00', $post['post_content']);

        return $post;
    }

    /**
     * Replace all internal links in Post content.
     */
    public function replaceInternalLinks(string $destinationPostContent, AbstractPostProcessor $shareProcessor): string
    {
        static $REGEX_POST = '~<a.*?href="(?<URL>.*?)".*?data-type="(?<TYPE>.*?)".*?data-id="(?<ID>\d+)".*?>~';

        \preg_match_all($REGEX_POST, $destinationPostContent, $linksPosts, \PREG_SET_ORDER, 0);

        foreach ($linksPosts as $post) {
            $destinationPost = $this->findDestinationPost((int) $post['ID'], $shareProcessor);

            if (null === $destinationPost) {
                $destinationPostContent = \str_replace(
                    [
                        " data-type=\"{$post['TYPE']}\"",
                        " data-id=\"{$post['ID']}\"",
                    ],
                    [
                        '',
                        '',
                    ],
                    $destinationPostContent
                );

                continue;
            }

            $postUrl = \get_permalink($destinationPost);
            $postType = \get_post_type($destinationPost);

            $destinationPostContent = \str_replace(
                [
                    "href=\"{$post['URL']}\"",
                    "data-type=\"{$post['TYPE']}\"",
                    "data-id=\"{$post['ID']}\"",
                ],
                [
                    "href=\"{$postUrl}\"",
                    "data-type=\"{$postType}\"",
                    "data-id=\"{$destinationPost->ID}\"",
                ],
                $destinationPostContent
            );
        }

        return $destinationPostContent;
    }

    /**
     * Duplicate single media file.
     */
    public static function duplicateMedia(int $attachmentId, int $sourceBlogId): ?\WP_Post
    {
        if (0 === $attachmentId) {
            return null;
        }

        $currentBlogId = \get_current_blog_id();
        $sourceMediaPost = self::safeSwitchToBlog($sourceBlogId, 'get_post', $attachmentId);

        $foundedPost = \get_posts([
            'post_type'      => $sourceMediaPost->post_type,
            'posts_per_page' => 1,
            'name'           => $sourceMediaPost->post_name,
        ]);

        if (1 === \count($foundedPost)) {
            return \reset($foundedPost) ?: null;
        }

        try {
            $shareProcessor = new AttachmentPostProcessor($sourceBlogId, $currentBlogId);
            $shareProcessor->setSourceObject(new AttachmentPostData($sourceMediaPost->ID, $sourceBlogId));
            $shareProcessor->share();

            return $shareProcessor->getDestinationObject()->getPost();
        } catch (ShareProcessException $e) {
            return new class() extends \WP_Post {
                public $ID = 0;
            };
        }
    }

    /**
     * Find source blog Post date for replace.
     */
    private function findDestinationPost(int $sourcePostId, AbstractPostProcessor $shareProcessor): ?\WP_Post
    {
        $sourcePost = self::safeSwitchToBlog(
            $shareProcessor->getSourceBlogId(),
            'get_post',
            $sourcePostId
        );

        if (null === $sourcePost) {
            return null;
        }

        $destinationPost = \get_posts([
            'name'           => $sourcePost->post_name,
            'post_type'      => $sourcePost->post_type,
            'posts_per_page' => 1,
            'post_status'    => 'publish',
        ]);

        return 1 === \count($destinationPost) ? \reset($destinationPost) : null;
    }

    /**
     * Parse and rebuild blocks.
     */
    private function parseBlocks(array $blocks): array
    {
        for ($i = 0, $max = \count($blocks); $i < $max; ++$i) {
            if ($blocks[$i]['innerBlocks'] ?? []) {
                $blocks[$i]['innerBlocks'] = $this->parseBlocks($blocks[$i]['innerBlocks']);
            }

            $blocks[$i] = $this->rebuildBlock($blocks[$i]);
        }

        return $blocks;
    }

    /**
     * Rebuild single block.
     */
    private function rebuildBlock(array $block): array
    {
        switch ($block['blockName']) {
            case 'core/gallery':
                return $this->rebuildCoreGalleryBlock($block);
            case 'core/image':
                return $this->rebuildCoreImageBlock($block);
            case 'core/cover':
                return $this->rebuildCoreCoverBlock($block);
            case 'core/video':
            case 'core/audio':
                return $this->rebuildCoreAudioBlock($block);
            case 'core/media-text':
                return $this->rebuildCoreMediaTextBlock($block);
            case 'core/file':
                return $this->rebuildCoreFileBlock($block);
            default:
                /**
                 * Fire rebuild custom block filter.
                 *
                 * @param array block
                 * @param int   source blog ID
                 */
                return (array) \apply_filters('Statik\Sharing\rebuildCustomBlock', $block, $this->sourceBlogId);
        }
    }

    /**
     * Rebuild Statik/File block.
     */
    private function rebuildCoreFileBlock(array $block): array
    {
        static $REGEX = '~<a.+?href="(?<URL>.*?)".*?>~';

        $newAttachment = self::duplicateMedia((int) $block['attrs']['id'], $this->sourceBlogId);
        $block['attrs']['id'] = $newAttachment->ID;
        $block['attrs']['href'] = \wp_get_attachment_url($newAttachment->ID);

        \preg_match($REGEX, $block['innerHTML'], $file);

        $block['innerContent'][0] = \str_replace(
            "href=\"{$file['URL']}\"",
            "href=\"{$block['attrs']['href']}\"",
            $block['innerContent'][0]
        );

        return $block;
    }

    /**
     * Rebuild Core/Gallery block.
     */
    private function rebuildCoreGalleryBlock(array $block): array
    {
        static $REGEX = '~<img.*?src="(?<SRC>.*?)".*?data-id="(?<ID>[\d]+)".*?data-full-url="(?<FULL_URL>.*?)".*?data-link="(?<LINK>.*?)".*?class="(?<CLASS>.*?)".*?\/>~';

        $idsMapping = [];
        foreach ($block['attrs']['ids'] as $key => $id) {
            $newAttachment = self::duplicateMedia((int) $id, $this->sourceBlogId);

            if (null === $newAttachment) {
                continue;
            }

            $idsMapping[$id] = $block['attrs']['ids'][$key] = $newAttachment->ID;
        }

        \preg_match_all($REGEX, $block['innerHTML'], $images, \PREG_SET_ORDER, 0);

        foreach ($images as $image) {
            if (false === isset($idsMapping[$image['ID']])) {
                continue;
            }

            $imageSrc = \wp_get_attachment_image_url($idsMapping[$image['ID']], $block['attrs']['sizeSlug'] ?? 'large');
            $imageFullSrc = \wp_get_attachment_image_url($idsMapping[$image['ID']], 'full');
            $imagePermalink = \get_permalink($idsMapping[$image['ID']]);

            $block['innerContent'][0] = \str_replace(
                [
                    "src=\"{$image['SRC']}\"",
                    "data-full-url=\"{$image['FULL_URL']}\"",
                    "data-link=\"{$image['LINK']}\"",
                    "data-id=\"{$image['ID']}\"",
                    "class=\"{$image['CLASS']}\"",
                ],
                [
                    "src=\"{$imageSrc}\"",
                    "data-full-url=\"{$imageFullSrc}\"",
                    "data-link=\"{$imagePermalink}\"",
                    "data-id=\"{$idsMapping[$image['ID']]}\"",
                    "class=\"wp-image-{$idsMapping[$image['ID']]}\"",
                ],
                $block['innerContent'][0]
            );
        }

        return $block;
    }

    /**
     * Rebuild Core/Image block.
     */
    private function rebuildCoreImageBlock(array $block): array
    {
        static $REGEX_IMG = '~<img.*?src="(?<SRC>.*?)".*?class="(?<CLASS>.*?)".*?\/>~';
        static $REGEX_URL = '~<a.+?href="(?<URL>.*?)".*?>~';

        $newAttachment = self::duplicateMedia((int) $block['attrs']['id'], $this->sourceBlogId);

        if (null === $newAttachment) {
            return $block;
        }

        $block['attrs']['id'] = $newAttachment->ID;

        \preg_match($REGEX_IMG, $block['innerHTML'], $image);
        \preg_match($REGEX_URL, $block['innerHTML'], $url);

        switch ($block['attrs']['linkDestination'] ?? '') {
            case 'media':
                $linkDestination = \wp_get_attachment_url($newAttachment->ID);
                break;
            case 'attachment':
                $linkDestination = \get_permalink($newAttachment->ID);
                break;
            default:
                $linkDestination = $url['URL'];
        }

        $imageSrc = \wp_get_attachment_image_url($newAttachment->ID, $block['attrs']['sizeSlug']);

        $block['innerContent'][0] = \str_replace(
            [
                "src=\"{$image['SRC']}\"",
                "class=\"{$image['CLASS']}\"",
                "href=\"{$url['URL']}\"",
            ],
            [
                "src=\"{$imageSrc}\"",
                "class=\"wp-image-{$newAttachment->ID}\"",
                "href=\"{$linkDestination}\"",
            ],
            $block['innerContent'][0]
        );

        return $block;
    }

    /**
     * Rebuild Core/Image block.
     */
    private function rebuildCoreCoverBlock(array $block): array
    {
        static $REGEX_BG_IMAGE = '~style="background-image:url\((?<URL>.*?)\)"~';

        $newAttachment = self::duplicateMedia((int) $block['attrs']['id'], $this->sourceBlogId);

        if (null === $newAttachment) {
            return $block;
        }

        $block['attrs']['id'] = $newAttachment->ID;
        $block['attrs']['url'] = \wp_get_attachment_image_url($newAttachment->ID, 'full');

        \preg_match($REGEX_BG_IMAGE, $block['innerHTML'], $image);

        $block['innerContent'][0] = \str_replace(
            "background-image:url({$image['URL']})",
            "background-image:url({$block['attrs']['url']})",
            $block['innerContent'][0]
        );

        return $block;
    }

    /**
     * Rebuild Core/Image block.
     */
    private function rebuildCoreMediaTextBlock(array $block): array
    {
        static $REGEX_SRC = '~src="(?<SRC>.*?)"~';
        static $REGEX_IMG = '~<img.*?src="(?<SRC>.*?)".*?class="(?<CLASS>.*?)".*?\/>~';
        static $REGEX_URL = '~<figure class="wp-block-media-text__media"><a.+?href="(?<URL>.*?)".*?>~';

        $newAttachment = self::duplicateMedia((int) $block['attrs']['mediaId'], $this->sourceBlogId);

        if (null === $newAttachment) {
            return $block;
        }

        $block['attrs']['mediaId'] = $newAttachment->ID;
        $block['attrs']['mediaLink'] = \get_permalink($newAttachment->ID);

        \preg_match($REGEX_SRC, $block['innerHTML'], $file);
        \preg_match($REGEX_URL, $block['innerHTML'], $url);

        if ('image' === $block['attrs']['mediaType']) {
            \preg_match($REGEX_IMG, $block['innerHTML'], $image);

            $imageSrc = \wp_get_attachment_image_url($newAttachment->ID, 'large');

            $block['innerContent'][0] = \str_replace(
                [
                    "src=\"{$image['SRC']}\"",
                    "class=\"{$image['CLASS']}\"",
                ],
                [
                    "src=\"{$imageSrc}\"",
                    "class=\"wp-image-{$newAttachment->ID}\"",
                ],
                $block['innerContent'][0]
            );
        } else {
            $imagePermalink = \wp_get_attachment_url($newAttachment->ID);

            $block['innerContent'][0] = \str_replace(
                "src=\"{$file['SRC']}\"",
                "src=\"{$imagePermalink}\"",
                $block['innerContent'][0]
            );
        }

        switch ($block['attrs']['linkDestination'] ?? '') {
            case 'media':
                $linkDestination = \wp_get_attachment_url($newAttachment->ID);
                break;
            case 'attachment':
                $linkDestination = \get_permalink($newAttachment->ID);
                break;
            default:
                $linkDestination = $url['URL'];
        }

        $block['innerContent'][0] = \str_replace(
            "href=\"{$url['URL']}\"",
            "href=\"{$linkDestination}\"",
            $block['innerContent'][0]
        );

        return $block;
    }

    /**
     * Rebuild Core/Video and Core/Audio block.
     */
    private function rebuildCoreAudioBlock(array $block): array
    {
        static $REGEX = '~<audio.*?src="(?<URL>.*?)".*?><\/audio>~';

        $newAttachment = self::duplicateMedia((int) $block['attrs']['id'], $this->sourceBlogId);

        if (null === $newAttachment) {
            return $block;
        }

        $block['attrs']['id'] = $newAttachment->ID;

        \preg_match($REGEX, $block['innerHTML'], $image);

        $mediaUrl = \wp_get_attachment_url($newAttachment->ID);

        $block['innerContent'][0] = \str_replace(
            "src=\"{$image['URL']}\"",
            "src=\"{$mediaUrl}\"",
            $block['innerContent'][0]
        );

        return $block;
    }
}
