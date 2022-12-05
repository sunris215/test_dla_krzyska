<?php

declare(strict_types=1);

namespace Statik\Sharing\Integration\Plugin;

use Statik\Gutenberg\DI;
use Statik\Gutenberg\Rest\V1\GutenbergBlock;
use Statik\Sharing\Helper\SwitcherTrait;
use Statik\Sharing\Share\Processor\Post\AbstractPostProcessor;

/**
 * Class StatikGutenbergPluginIntegrator.
 */
class StatikGutenbergPluginIntegrator extends AbstractPluginIntegrator
{
    use SwitcherTrait;

    /**
     * StatikGutenbergPluginIntegrator constructor.
     */
    public function __construct()
    {
        \add_filter('Statik\Sharing\excludedPostMetaKeys', [$this, 'addExcludedMetaKeys']);
        \add_filter('Statik\Sharing\editPostLink', [$this, 'addPostEditLink'], 10, 2);
    }

    /**
     * {@inheritdoc}
     */
    public static function canBeEnabled(): bool
    {
        return \class_exists('Statik\Gutenberg\Plugin');
    }

    /**
     * Exclude plugin Post meta from sharing.
     */
    public function addExcludedMetaKeys(array $excludedMetaKeys): array
    {
        return \array_merge([GutenbergBlock::META_NAME], $excludedMetaKeys);
    }

    /**
     * Add post edit link for display iframe.
     */
    public function addPostEditLink(?string $editLink, AbstractPostProcessor $shareProcessor): ?string
    {
        $sourcePostType = $shareProcessor->getSourceObject()->getPost()->post_type;

        if (false === \in_array($sourcePostType, (array) DI::Config()->get('cpt.value', []))) {
            return $editLink;
        }

        $editLink = (string) self::safeSwitchToBlog(
            $shareProcessor->getDestinationBlogId(),
            'get_edit_post_link',
            $shareProcessor->getDestinationObject()->getPost()->ID,
            'edit'
        );

        return \add_query_arg('statik', 'update-sharing-replica', $editLink);
    }
}
