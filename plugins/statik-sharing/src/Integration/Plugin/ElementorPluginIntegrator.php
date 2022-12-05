<?php

declare(strict_types=1);

namespace Statik\Sharing\Integration\Plugin;

use Elementor\Plugin;
use Illuminate\Support\Arr;
use Statik\Sharing\Helper\ReplaceIdTrait;
use Statik\Sharing\Helper\SwitcherTrait;
use Statik\Sharing\Post\PostMeta;
use Statik\Sharing\Share\Exception\ShareProcessException;
use Statik\Sharing\Share\Processor\Post\AbstractPostProcessor;
use Statik\Sharing\Share\ShareManager;

/**
 * Class ElementorPluginIntegrator.
 */
class ElementorPluginIntegrator extends AbstractPluginIntegrator
{
    use ReplaceIdTrait;
    use SwitcherTrait;

    public const DATA_META_KEY = '_elementor_data';

    private ?Plugin $elementor;

    /**
     * ElementorPluginIntegrator constructor.
     */
    public function __construct()
    {
        $this->elementor = Plugin::instance();

        \add_action('Statik\Share\afterInsertPostMeta', [$this, 'rebuildElementorMeta']);
        \add_filter('Statik\Sharing\excludedPostMetaKeys', [$this, 'addExcludedMetaKeys']);
        \add_action('elementor/editor/after_save', [$this, 'triggerSharingSave'], 200, 2);
    }

    /**
     * {@inheritdoc}
     */
    public static function canBeEnabled(): bool
    {
        return \defined('ELEMENTOR_VERSION') && \class_exists('Elementor\Plugin');
    }

    /**
     * Trigger share after save post in the Elementor frontend editor.
     */
    public function triggerSharingSave(int $postId, array $elementorData): void
    {
        $postMeta = new PostMeta($postId);

        if ($postMeta->isSharingTypePrimary()) {
            foreach ($postMeta->getMeta() as $blogId => $postData) {
                if (empty($postData) || false === \is_object($postData) || empty($postData->postId)) {
                    continue;
                }

                try {
                    $manager = new ShareManager(\get_current_blog_id(), $postId, (int) $blogId, $postData->postId);
                    $manager->sharePost();
                } catch (ShareProcessException $e) {
                }
            }
        }
    }

    /**
     * Exclude plugin Post meta from sharing.
     */
    public function addExcludedMetaKeys(array $excludedMetaKeys): array
    {
        return \array_merge(['_elementor_css'], $excludedMetaKeys);
    }

    /**
     * Rebuild post Elementor meta data.
     */
    public function rebuildElementorMeta(AbstractPostProcessor $processor): void
    {
        $this->processor = $processor;
        $blogId = $this->processor->getDestinationBlogId();
        $postId = $this->processor->getDestinationObject()->getPost()->ID;

        if (false === $this->isElementorPost($postId)) {
            return;
        }

        $dataMeta = self::safeSwitchToBlog($blogId, 'get_post_meta', $postId, self::DATA_META_KEY, true);
        $dataMeta = \json_decode($dataMeta, true);

        if (\JSON_ERROR_NONE !== \json_last_error()) {
            return;
        }

        \array_walk($dataMeta, [$this, 'rebuildWidgetDynamicContent']);

        $dataMeta = \wp_slash(\json_encode($dataMeta));
        self::safeSwitchToBlog($blogId, 'update_post_meta', $postId, self::DATA_META_KEY, $dataMeta);

        $this->elementor->files_manager->clear_cache();
    }

    /**
     * Fix ID mapping in single module.
     */
    private function rebuildWidgetDynamicContent(array &$element): array
    {
        $this->replaceImages($element, 'settings._background_image');
        $this->replaceImages($element, 'settings._background_image_tablet');
        $this->replaceImages($element, 'settings._background_image_mobile');
        $this->replaceImages($element, 'settings._background_hover_image');
        $this->replaceImages($element, 'settings._background_hover_image_tablet');
        $this->replaceImages($element, 'settings._background_hover_image_mobile');

        $this->replaceDynamicShortcode($element, 'settings.__dynamic__');

        $switchTest = isset($element['widgetType'])
            ? "widgetType.{$element['widgetType']}"
            : (isset($element['elType']) ? "elType.{$element['elType']}" : null);

        switch ($switchTest) {
            case 'elType.section':
            case 'elType.column':
                $this->replaceImages($element, 'settings.background_image');
                $this->replaceImages($element, 'settings.background_image_tablet');
                $this->replaceImages($element, 'settings.background_image_mobile');
                $this->replaceImages($element, 'settings.background_hover_image');
                $this->replaceImages($element, 'settings.background_hover_image_tablet');
                $this->replaceImages($element, 'settings.background_hover_image_mobile');
                $this->replaceImages($element, 'settings.background_slideshow_gallery', false);
                break;
            case 'widgetType.template':
                $this->replacePosts($element, 'settings.template_id');
                break;
            case 'widgetType.icon':
            case 'widgetType.icon-box':
            case 'widgetType.button':
                $this->replaceImages($element, 'settings.selected_icon.value');
                break;
            case 'widgetType.image':
            case 'widgetType.image-box':
                $this->replaceImages($element, 'settings.image');
                break;
            case 'widgetType.posts':
            case 'widgetType.portfolio':
                $this->replacePosts($element, 'settings.posts_exclude_ids');
                $this->replacePosts($element, 'settings.posts_posts_ids');
                $this->replaceTerms($element, 'settings.posts_include_term_ids');
                $this->replaceTerms($element, 'settings.posts_exclude_term_ids');
                break;
            case 'widgetType.gallery':
                $this->replaceImages($element, 'settings.gallery', false);
                $this->replaceImagesArray($element, 'settings.galleries', 'multiple_gallery', false);
                break;
            case 'widgetType.slides':
                $this->replaceImagesArray($element, 'settings.slides', 'background_image');
                break;
            case 'widgetType.image-carousel':
                $this->replaceImages($element, 'settings.carousel');
                break;
            case 'widgetType.call-to-action':
                $this->replaceImages($element, 'settings.bg_image');
                $this->replaceImages($element, 'settings.graphic_image');
                break;
            case 'widgetType.reviews':
            case 'widgetType.media-carousel':
            case 'widgetType.testimonial-carousel':
                $this->replaceImagesArray($element, 'settings.slides', 'image');
                break;
            case 'widgetType.image-gallery':
                $this->replaceImages($element, 'settings.wp_gallery');
                break;
            case 'widgetType.testimonial':
                $this->replaceImages($element, 'settings.testimonial_image');
                break;
            case 'widgetType.icon-list':
                $this->replaceImagesArray($element, 'settings.icon_list', 'selected_icon.value');
                break;
            default:
                /**
                 * Fire rebuild custom widget filter.
                 *
                 * @param array                 widget
                 * @param AbstractPostProcessor processor instance
                 * @param self                  Elementor plugin integration
                 */
                $element = (array) \apply_filters(
                    'Statik\Sharing\rebuildCustomElementorWidget',
                    $element,
                    $this->processor,
                    $this
                );
        }

        isset($element['elements']) && \array_walk($element['elements'], [$this, 'rebuildWidgetDynamicContent']);

        return $element;
    }

    /**
     * Replace dynamic shortcode.
     */
    private function replaceDynamicShortcode(array &$element, string $key): array
    {
        if (false === Arr::has($element, $key)) {
            return $element;
        }

        $settings = Arr::get($element, $key);

        $dynamicTags = $this->elementor->dynamic_tags;

        foreach ($settings as $settingKey => &$setting) {
            $setting = $dynamicTags->tag_text_to_tag_data($setting);

            if (isset($setting['settings']['attachment_id'])) {
                $this->replacePostId($setting['settings']['attachment_id']);
            } elseif (isset($setting['settings']['post_id'])) {
                $this->replacePostId($setting['settings']['post_id']);
            } elseif (isset($setting['settings']['popup'])) {
                $this->replacePostId($setting['settings']['popup']);
            } elseif (isset($setting['settings']['taxonomy_id'])) {
                $this->replaceTermId($setting['settings']['taxonomy_id']);
            }

            $setting = $dynamicTags->create_tag($setting['id'], $setting['name'], $setting['settings']);
            $setting->set_settings(\array_filter($setting->get_settings()));
            $settings[$settingKey] = $dynamicTags->tag_to_text($setting);
        }

        Arr::set($element, "{$key}", \array_filter($settings));

        return $element;
    }

    /**
     * Determinate if post ID supports the Elementor builder.
     */
    private function isElementorPost(int $postId): bool
    {
        return 'builder' === \get_post_meta($postId, '_elementor_edit_mode', true);
    }
}
