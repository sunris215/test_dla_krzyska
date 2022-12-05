<?php

declare(strict_types=1);

namespace Statik\Sharing\Helper;

use Illuminate\Support\Arr;
use Statik\Sharing\DI;
use Statik\Sharing\Share\Processor\Post\AbstractPostProcessor;

/**
 * Trait ReplaceIdTrait.
 */
trait ReplaceIdTrait
{
    use SwitcherTrait;

    private AbstractPostProcessor $processor;

    /**
     * Replace post ID between old and new blog.
     *
     * @param int|string $postId
     */
    public function replacePostId(&$postId): ?int
    {
        global $wpdb;

        /** @var \WP_Post|null $sourcePost */
        $sourcePost = self::safeSwitchToBlog($this->processor->getSourceBlogId(), 'get_post', $postId);

        if (\is_wp_error($sourcePost) || null === $sourcePost) {
            return null;
        }

        $query = $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts}
                WHERE post_name = %s AND post_type = %s;",
            $sourcePost->post_name,
            $sourcePost->post_type
        );

        $postId = $wpdb->get_var($query);

        null === $postId && DI::Logger()->warningShare(
            'replace_content_post',
            \sprintf(
                \__('Post %s (ID: %s) does not exists on destination blog', 'statik'),
                $sourcePost->post_title,
                $sourcePost->ID,
                $this->processor->getDestinationBlogId()
            ),
            $this->processor
        );

        return $postId ? (int) $postId : null;
    }

    /**
     * Replace term ID between old and new blog.
     *
     * @param int|string $termId
     */
    public function replaceTermId(&$termId): ?int
    {
        global $wpdb;

        /** @var \WP_Term|\WP_Error|null $sourceTerm */
        $sourceTerm = self::safeSwitchToBlog($this->processor->getSourceBlogId(), 'get_term', $termId);

        if (\is_wp_error($sourceTerm) || null === $sourceTerm) {
            return null;
        }

        $query = $wpdb->prepare(
            "SELECT {$wpdb->terms}.term_id FROM {$wpdb->terms}
                LEFT JOIN {$wpdb->term_taxonomy}
                ON {$wpdb->terms}.term_id = {$wpdb->term_taxonomy}.term_id
                WHERE slug = %s AND taxonomy = %s;",
            $sourceTerm->slug,
            $sourceTerm->taxonomy
        );

        $termId = $wpdb->get_var($query);

        null === $termId && DI::Logger()->warningShare(
            'replace_content_terms',
            \sprintf(
                \__('Term %s (ID: %s) does not exists on destination blog', 'statik'),
                $sourceTerm->name,
                $sourceTerm->term_id,
                $this->processor->getDestinationBlogId()
            ),
            $this->processor
        );

        return $termId ? (int) $termId : null;
    }

    /**
     * Replace nav menu ID between old and new blog.
     *
     * @param int|string $menuId
     */
    public function replaceNavMenuId(&$menuId): ?int
    {
        global $wpdb;

        /** @var \WP_Term|false $sourceNavMenu */
        $sourceNavMenu = self::safeSwitchToBlog(
            $this->processor->getSourceBlogId(),
            'wp_get_nav_menu_object',
            $menuId
        );

        if (false === $sourceNavMenu) {
            return null;
        }

        $query = $wpdb->prepare(
            "SELECT {$wpdb->terms}.term_id FROM {$wpdb->terms}
                LEFT JOIN {$wpdb->term_taxonomy}
                ON {$wpdb->terms}.term_id = {$wpdb->term_taxonomy}.term_id
                WHERE slug = %s AND taxonomy = %s;",
            $sourceNavMenu->slug,
            $sourceNavMenu->taxonomy
        );

        $navMenuId = $wpdb->get_var($query);

        null === $navMenuId && DI::Logger()->warningShare(
            'replace_content_nav_menu',
            \sprintf(
                \__('Nav menu %s (ID: %s) does not exists on destination blog', 'statik'),
                $sourceNavMenu->name,
                $sourceNavMenu->term_id,
                $this->processor->getDestinationBlogId()
            ),
            $this->processor
        );

        return $navMenuId ? (int) $navMenuId : null;
    }

    /**
     * Rebuild gallery images IDs.
     */
    private function replaceImagesArray(array &$element, string $key, string $secondKey, bool $single = true): array
    {
        if (false === Arr::has($element, $key)) {
            return $element;
        }

        $settings = Arr::get($element, $key);

        if (empty($settings) || false === \is_array($settings)) {
            return $element;
        }

        foreach ($settings as $settingKey => &$setting) {
            Arr::set(
                $element,
                "{$key}.{$settingKey}",
                $this->replaceImages($setting, $secondKey, $single)
            );
        }

        return $element;
    }

    /**
     * Replace image array.
     */
    private function replaceImages(array &$element, string $key, bool $single = true): ?array
    {
        if (false === Arr::has($element, $key)) {
            return $element;
        }

        $settings = Arr::get($element, $key);

        if (empty($settings) || false === \is_array($settings)) {
            return $element;
        }

        if ($single) {
            if (2 === \count($settings) && isset($settings['id'])) {
                Arr::set($element, "{$key}.id", $this->replacePostId($settings['id']));
                Arr::set($element, "{$key}.url", \wp_get_attachment_image_url($settings['id'], 'full'));
            }

            return $element;
        }

        foreach ($settings as $settingKey => $setting) {
            Arr::set($element, "{$key}.{$settingKey}.id", $this->replacePostId($setting['id']));
            Arr::set($element, "{$key}.{$settingKey}.url", \wp_get_attachment_image_url($setting['id'], 'full'));
        }

        return $element;
    }

    /**
     * Rebuild Posts terms IDs.
     */
    private function replaceTerms(array &$element, string $key): array
    {
        if (false === Arr::has($element, $key)) {
            return $element;
        }

        $settings = Arr::get($element, $key);

        if (empty($settings)) {
            return $element;
        }

        if (\is_numeric($settings)) {
            Arr::set($element, $key, $this->replaceTermId($settings));

            return $element;
        }

        if (false === \is_array($settings)) {
            return $element;
        }

        foreach ($settings as $settingKey => &$setting) {
            Arr::set($element, "{$key}.{$settingKey}", $this->replaceTermId($setting));
        }

        Arr::set($element, $key, \array_filter($settings));

        return $element;
    }

    /**
     * Rebuild Posts IDs.
     */
    private function replacePosts(array &$element, string $key): array
    {
        if (false === Arr::has($element, $key)) {
            return $element;
        }

        $settings = Arr::get($element, $key);

        if (empty($settings)) {
            return $element;
        }

        if (\is_numeric($settings)) {
            Arr::set($element, $key, $this->replacePostId($settings));

            return $element;
        }

        if (false === \is_array($settings)) {
            return $element;
        }

        foreach ($settings as $settingKey => &$setting) {
            Arr::set($element, "{$key}.{$settingKey}", $this->replacePostId($setting));
        }

        Arr::set($element, $key, \array_filter($settings));

        return $element;
    }

    /**
     * Rebuild nav menu IDs.
     */
    private function replaceNavMenu(array &$element, string $key): array
    {
        if (false === Arr::has($element, $key)) {
            return $element;
        }

        $settings = Arr::get($element, $key);

        if (empty($settings)) {
            return $element;
        }

        if (\is_numeric($settings)) {
            Arr::set($element, $key, $this->replaceNavMenuId($settings));

            return $element;
        }

        if (false === \is_array($settings)) {
            return $element;
        }

        foreach ($settings as $settingKey => &$setting) {
            Arr::set($element, "{$key}.{$settingKey}", $this->replaceNavMenuId($setting));
        }

        Arr::set($element, $key, \array_filter($settings));

        return $element;
    }

    /**
     * Rebuild multiple posts IDs.
     */
    private function replacePostsArray(array &$element, string $key, string $secondKey): array
    {
        if (false === Arr::has($element, $key)) {
            return $element;
        }

        $settings = Arr::get($element, $key);

        if (empty($settings) || false === \is_array($settings)) {
            return $element;
        }

        foreach ($settings as $settingKey => &$setting) {
            Arr::set(
                $element,
                "{$key}.{$settingKey}",
                $this->replacePosts($setting, $secondKey)
            );
        }

        return $element;
    }
}
