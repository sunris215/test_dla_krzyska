<?php

declare(strict_types=1);

namespace Statik\Sharing\Share;

use Statik\Sharing\DI;
use Statik\Sharing\Share\Data\AttachmentPostData;
use Statik\Sharing\Share\Data\PostData;
use Statik\Sharing\Share\Exception\ShareProcessException;
use Statik\Sharing\Share\Processor\AbstractProcessor;
use Statik\Sharing\Share\Processor\Post\AttachmentPostProcessor;
use Statik\Sharing\Share\Processor\Post\PostProcessor;
use Statik\Sharing\Share\Processor\ProcessorInterface;

/**
 * Class ShareManager.
 */
class ShareManager
{
    private int $sourceBlogId;

    private int $sourcePostId;

    private int $destinationBlogId;

    private ?int $destinationPostId;

    /**
     * ShareManager constructor.
     */
    public function __construct(
        int $sourceBlogId,
        int $sourcePostId,
        int $destinationBlogId,
        int $destinationPostId = null
    ) {
        $this->sourceBlogId = $sourceBlogId;
        $this->sourcePostId = $sourcePostId;
        $this->destinationBlogId = $destinationBlogId;
        $this->destinationPostId = $destinationPostId;
    }

    /**
     * Share post from the source blog to the destination blog.
     * This processor can be used to all post types expect attachments.
     *
     * @throws ShareProcessException
     */
    public function sharePost(): ProcessorInterface
    {
        $processor = $this->getProcessor(PostProcessor::class);

        /**
         * Fire before share post action.
         *
         * @param AbstractProcessor processor instance
         */
        \do_action('Statik\Share\beforeSharePost', $processor);

        $this->callAction($processor, 'share');

        /**
         * Fire after share post action.
         *
         * @param AbstractProcessor processor instance
         */
        \do_action('Statik\Share\afterSharePost', $processor);

        return $processor;
    }

    /**
     * Delete post from the destination blog based on the source blog.
     * This processor can be used to all post types expect attachments.
     *
     * @throws ShareProcessException
     */
    public function deletePost(): ProcessorInterface
    {
        $processor = $this->getProcessor(PostProcessor::class);

        /**
         * Fire before delete post action.
         *
         * @param AbstractProcessor processor instance
         */
        \do_action('Statik\Share\beforeDeletePost', $processor);

        $this->callAction($processor, 'delete');

        /**
         * Fire after delete post action.
         *
         * @param AbstractProcessor processor instance
         */
        \do_action('Statik\Share\afterDeletePost', $processor);

        return $processor;
    }

    /**
     * Detach the source blog and the destination blog. All sharing metas will be removed.
     * This processor can be used to all post types expect attachments.
     *
     * @throws ShareProcessException
     */
    public function detachPost(): ProcessorInterface
    {
        $processor = $this->getProcessor(PostProcessor::class);

        /**
         * Fire before detach post action.
         *
         * @param AbstractProcessor processor instance
         */
        \do_action('Statik\Share\beforeDetachPost', $processor);

        $this->callAction($processor, 'detach');

        /**
         * Fire after detach post action.
         *
         * @param AbstractProcessor processor instance
         */
        \do_action('Statik\Share\afterDetachPost', $processor);

        return $processor;
    }

    /**
     * Share attachment from the source blog to the destination blog.
     * This processor can be used only to attachments post type.
     *
     * @throws ShareProcessException
     */
    public function shareAttachment(): ProcessorInterface
    {
        $processor = $this->getProcessor(AttachmentPostProcessor::class);

        /**
         * Fire before share attachment action.
         *
         * @param AbstractProcessor processor instance
         */
        \do_action('Statik\Share\beforeShareAttachment', $processor);

        $this->callAction($processor, 'share');

        /**
         * Fire after share attachment action.
         *
         * @param AbstractProcessor processor instance
         */
        \do_action('Statik\Share\afterShareAttachment', $processor);

        return $processor;
    }

    /**
     * Dynamically get the processor instance based on the processor name.
     */
    private function getProcessor(string $processorName): AbstractProcessor
    {
        switch ($processorName) {
            case AttachmentPostProcessor::class:
                $dataObject = AttachmentPostData::class;
                break;
            case PostProcessor::class:
            default:
                $dataObject = PostData::class;
        }

        $sourcePostData = new $dataObject($this->sourcePostId, $this->sourceBlogId);
        $destinationPostData = $this->destinationPostId
            ? new $dataObject($this->destinationPostId, $this->destinationBlogId)
            : null;

        $processor = new $processorName($this->sourceBlogId, $this->destinationBlogId);
        $processor->setSourceObject($sourcePostData);
        $processor->setDestinationObject($destinationPostData);

        return $processor;
    }

    /**
     * Call processor action to share, detach or delete and keep everything logged.
     *
     * @throws ShareProcessException
     */
    private function callAction(AbstractProcessor $processor, string $method): AbstractProcessor
    {
        try {
            $processor->{$method}();

            DI::Logger()->infoShare("{$method}_post", $processor);
        } catch (ShareProcessException $error) {
            DI::Logger()->errorShare($method, $error->getMessage(), $processor);

            throw $error;
        }

        return $processor;
    }
}
