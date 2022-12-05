<?php

declare(strict_types=1);

namespace Statik\Sharing\Share\Processor\Post;

use Statik\Sharing\Helper\SwitcherTrait;
use Statik\Sharing\Share\Data\AttachmentPostData;
use Statik\Sharing\Share\Exception\ShareProcessException;

/**
 * Class AttachmentProcessor.
 */
class AttachmentPostProcessor extends AbstractPostProcessor
{
    use SwitcherTrait;

    /**
     * {@inheritdoc}
     *
     * @throws ShareProcessException
     */
    public function share(): bool
    {
        parent::share();

        self::switchToBlog($this->destinationBlogId, [$this, 'insertPostIntoDestinationBlog']);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(): bool
    {
        throw new ShareProcessException(\__('Attachment cannot be deleted by share processor'));
    }

    /**
     * {@inheritdoc}
     */
    public function detach(): bool
    {
        throw new ShareProcessException(\__('Attachment cannot be detached by share processor'));
    }

    /**
     * {@inheritdoc}
     */
    protected function validate(): void
    {
        parent::validate();

        if ('attachment' !== $this->sourceObject->getPost()->post_type) {
            throw new ShareProcessException(
                \__('Attachment processor can be used to process only attachments', 'statik')
            );
        }

        if (false === $this->sourceObject instanceof AttachmentPostData) {
            throw new ShareProcessException(
                \__('Attachment processor requires the source object to be an instance of AttachmentPostData')
            );
        }
    }
}
