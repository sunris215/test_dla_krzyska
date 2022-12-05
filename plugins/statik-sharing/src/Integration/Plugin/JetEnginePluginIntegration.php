<?php

declare(strict_types=1);

namespace Statik\Sharing\Integration\Plugin;

use Statik\Sharing\Helper\ReplaceIdTrait;
use Statik\Sharing\Helper\SwitcherTrait;
use Statik\Sharing\Share\Processor\Post\AbstractPostProcessor;

/**
 * Class JetEnginePluginIntegration.
 */
class JetEnginePluginIntegration extends AbstractPluginIntegrator
{
    use ReplaceIdTrait;
    use SwitcherTrait;

    private ElementorPluginIntegrator $integration;

    /**
     * JetEnginePluginIntegration constructor.
     */
    public function __construct()
    {
        \add_action('Statik\Share\afterInsertPostMeta', [$this, 'rebuildRelationsMeta']);
        \add_filter('Statik\Sharing\rebuildCustomElementorWidget', [$this, 'rebuildWidgetDynamicContent'], 10, 3);
    }

    /**
     * {@inheritdoc}
     */
    public static function canBeEnabled(): bool
    {
        return \class_exists('Jet_Engine');
    }

    /**
     * Rebuild Jet Engine relations meta.
     */
    public function rebuildRelationsMeta(AbstractPostProcessor $processor): void
    {
        $this->processor = $processor;

        $blogId = $this->processor->getDestinationBlogId();
        $postId = $this->processor->getDestinationObject()->getPost()->ID;

        foreach ($processor->getDestinationObject()->getMetaKeys() as $metaKey) {
            if (0 !== \strpos($metaKey, 'relation_')) {
                continue;
            }

            $metaValue = self::safeSwitchToBlog($blogId, 'get_post_meta', $postId, $metaKey, true);
            $this->replacePostId($metaValue);
            self::safeSwitchToBlog($blogId, 'update_post_meta', $postId, $metaKey, $metaValue);
        }
    }

    /**
     *  Rebuild Jet Engine plugin widgets.
     */
    public function rebuildWidgetDynamicContent(
        array $element,
        AbstractPostProcessor $processor,
        ElementorPluginIntegrator $integration
    ): array {
        $this->processor = $processor;
        $this->integration = $integration;

        $switchTest = isset($element['widgetType'])
            ? "widgetType.{$element['widgetType']}"
            : (isset($element['elType']) ? "elType.{$element['elType']}" : null);

        switch ($switchTest) {
            case 'widgetType.jet-listing-grid':
                $this->replacePosts($element, 'lisitng_id');
                $this->replaceListingGrid($element);
                break;
            case 'widgetType.jet-nav-menu':
                $this->replaceNavMenu($element, 'settings.nav_menu');
                break;
            case 'widgetType.jet-tabs':
                $this->replacePostsArray($element, 'settings.tabs', 'item_template_id');
                break;
            case 'widgetType.jet-video':
                $this->replaceImages($element, 'settings.selected_play_button_icon.value');
                $this->replaceImages($element, 'settings.play_button_image');
                break;
            case 'widgetType.jet-slider':
                $this->replaceImages($element, 'settings.selected_slider_navigation_icon_arrow.value');
                break;
            case 'widgetType.jet-listing-dynamic-link':
                $this->replaceImages($element, 'settings.selected_link_icon.value');
                break;
            case 'widgetType.jet-listing-dynamic-field':
                $this->replaceImages($element, 'settings.selected_field_icon.value');
                break;
            case 'widgetType.jet-smart-filters-search':
                $this->replacePosts($element, 'settings.filter_id');
                break;
        }

        return $element;
    }

    /**
     * Rebuild Listing Grid.
     */
    private function replaceListingGrid(array &$element): array
    {
        foreach ($element['settings']['posts_query'] ?? [] as $key => $postQuery) {
            switch ($postQuery['type']) {
                case 'posts_params':
                    $keysToReplace = ['posts_in', 'posts_not_in', 'posts_parent'];
                    $replaceMethod = 'replacePostId';
                    break;
                case 'tax_query':
                    $keysToReplace = ['tax_query_terms'];
                    $replaceMethod = 'replaceTermId';
                    break;
                default:
                    $keysToReplace = [];
                    $replaceMethod = null;
            }

            foreach ($keysToReplace as $keyToReplace) {
                if (false === isset($element['settings']['posts_query'][$key][$keyToReplace])) {
                    continue;
                }

                $ids = \explode(',', $element['settings']['posts_query'][$key][$keyToReplace]);
                \array_walk($ids, fn ($id) => $this->{$replaceMethod}($id));
                $element['settings']['posts_query'][$key][$keyToReplace] = \implode(',', $ids);
            }
        }

        $keysToReplace = ['terms_object_ids', 'terms_include', 'terms_exclude', 'terms_parent', 'terms_child_of'];

        foreach ($keysToReplace as $keyToReplace) {
            if (false === isset($element['settings'][$keyToReplace])) {
                continue;
            }

            $ids = \explode(',', $element['settings'][$keyToReplace]);
            \array_walk($ids, [$this->integration, 'replaceTermId']);
            $element['settings'][$keyToReplace] = \implode(',', $ids);
        }

        if (isset($element['settings']['lisitng_id'])) {
            $this->replacePostId($element['settings']['lisitng_id']);
        }

        return $element;
    }
}
