<?php

declare(strict_types=1);

namespace Statik\Sharing\Helper;

/**
 * Class CustomPostType.
 */
class CustomPostType
{
    use SwitcherTrait;

    /**
     * Get list of all available CPTs on all blogs.
     */
    public static function getAllCPTs(): array
    {
        $blogs = \get_sites([
            'archived' => 0,
            'spam'     => 0,
            'deleted'  => 0,
        ]);

        $postTypes = [];

        foreach ($blogs as $blog) {
            /** @var \WP_Site $blog */
            $postTypes = self::safeSwitchToBlog($blog->id, static function (array $postTypes): array {
                foreach (\get_post_types(['show_ui' => true], 'objects') as $postType) {
                    if (\in_array($postType->name, ['attachment', 'wp_block'], true)) {
                        continue;
                    }

                    $postTypes[$postType->name] = $postType->label;
                }

                return $postTypes;
            }, $postTypes);
        }

        return $postTypes;
    }
}
