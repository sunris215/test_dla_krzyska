<?php

declare(strict_types=1);

namespace Statik\Sharing\Share\Handler;

use Statik\Sharing\Helper\SwitcherTrait;
use Statik\Sharing\Share\Exception\ShareProcessException;
use Statik\Sharing\Share\ShareManager;

/**
 * Class CronShareHandler.
 */
class CronShareHandler implements HandlerInterface
{
    use SwitcherTrait;

    public const CRON_IN_PROGRESS = 'statik_cron_in_progress';

    /**
     * ShareManager constructor.
     */
    public function __construct()
    {
        \add_action('Statik\Sharing\handleCronShare', [$this, 'handleRequest'], 10, \PHP_INT_MAX);
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(...$args): bool
    {
        if (\count($args) < 4) {
            return false;
        }

        $defaultArgs = [null, null, null, null, null];
        [$shareType, $sourceBlogId, $sourcePostId, $destinationBlogId, $destinationPostId] = $args + $defaultArgs;

        try {
            $shareManager = new ShareManager($sourceBlogId, $sourcePostId, $destinationBlogId, $destinationPostId);

            if ('attachment' === $shareType) {
                $shareManager->shareAttachment();

                self::safeSwitchToBlog(
                    $destinationBlogId,
                    'delete_post_meta',
                    $destinationPostId,
                    self::CRON_IN_PROGRESS
                );
            } elseif ('post' === $shareType) {
                $shareManager->sharePost();
            }
        } catch (ShareProcessException $e) {
            return false;
        }

        return true;
    }
}
