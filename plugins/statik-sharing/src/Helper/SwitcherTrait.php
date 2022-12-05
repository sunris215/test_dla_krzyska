<?php

declare(strict_types=1);

namespace Statik\Sharing\Helper;

use Statik\Sharing\Share\Exception\ShareProcessException;

/**
 * Trait SwitcherTrait.
 */
trait SwitcherTrait
{
    /**
     * Save switch current blog.
     *
     * @param mixed ...$args
     *
     * @return bool|mixed|null
     */
    public static function safeSwitchToBlog(int $blogId, callable $function = null, ...$args)
    {
        try {
            return self::switchToBlog($blogId, $function, ...$args);
        } catch (ShareProcessException $e) {
            return null;
        }
    }

    /**
     * Switch current blog.
     *
     * @param mixed ...$args
     *
     * @throws ShareProcessException
     *
     * @see switch_to_blog()
     */
    public static function switchToBlog(int $blogId, callable $function = null, ...$args)
    {
        $currentBlogId = \get_current_blog_id();

        if ($currentBlogId !== $blogId) {
            \switch_to_blog($blogId);
        }

        if (null !== $function) {
            try {
                return \call_user_func_array($function, $args);
            } catch (ShareProcessException $e) {
                throw $e;
            } finally {
                \switch_to_blog($currentBlogId);
            }
        }

        return true;
    }
}
