<?php

declare(strict_types=1);

namespace Statik\Sharing\Integration\Plugin;

use DeliciousBrains\WP_Offload_Media\Items\Media_Library_Item;
use Statik\Sharing\Helper\SwitcherTrait;
use Statik\Sharing\Share\Processor\Post\AbstractPostProcessor;

/**
 * Class OffloadMediaPluginIntegrator.
 */
class OffloadMediaPluginIntegrator extends AbstractPluginIntegrator
{
    use SwitcherTrait;

    /**
     * OffloadMediaPluginIntegrator constructor.
     */
    public function __construct()
    {
        \add_filter('Statik\Sharing\excludedPostMetaKeys', [$this, 'addExcludedMetaKeys']);
        \add_action('Statik\Sharing\afterInsertPost', [$this, 'copyOffloadMediaRecord'], 10, 2);
    }

    /**
     * {@inheritdoc}
     */
    public static function canBeEnabled(): bool
    {
        return \class_exists('AS3CF_Compatibility_Check');
    }

    /**
     * Exclude plugin Post meta from sharing.
     */
    public function addExcludedMetaKeys(array $excludedMetaKeys): array
    {
        return \array_merge(['amazonS3_cache'], $excludedMetaKeys);
    }

    /**
     * Copy offload media record in database.
     */
    public function copyOffloadMediaRecord(array $post, AbstractPostProcessor $postProcessor): void
    {
        /** @var bool|Media_Library_Item $sourceItem */
        $sourceItem = self::safeSwitchToBlog(
            $postProcessor->getSourceBlogId(),
            [Media_Library_Item::class, 'get_by_source_id'],
            $postProcessor->getSourceObject()->getObject()->ID
        );

        if (false === $sourceItem) {
            return;
        }

        $newItem = new Media_Library_Item(
            $sourceItem->provider(),
            $sourceItem->region(),
            $sourceItem->bucket(),
            $sourceItem->path(),
            $sourceItem->is_private(),
            $post['ID'],
            $sourceItem->source_path(),
            null,
            $sourceItem->extra_info(),
            $sourceItem->originator(),
            $sourceItem->is_verified()
        );

        $newItem->save();
    }
}
