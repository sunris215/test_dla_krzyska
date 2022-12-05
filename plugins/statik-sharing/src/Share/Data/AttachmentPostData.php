<?php

declare(strict_types=1);

namespace Statik\Sharing\Share\Data;

/**
 * Class AttachmentPostData.
 */
class AttachmentPostData extends PostData
{
    protected ?string $attachmentFile;

    protected ?string $attachmentFileUrl;

    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->attachmentFile = \get_attached_file($this->id);
        $this->attachmentFileUrl = \wp_get_attachment_url($this->id);
    }

    /**
     * Get Post attachment file.
     */
    public function getAttachmentFile(): ?string
    {
        return $this->attachmentFile;
    }

    /**
     * Get Post attachment file URL.
     */
    public function getAttachmentFileUrl(): ?string
    {
        return $this->attachmentFileUrl;
    }
}
