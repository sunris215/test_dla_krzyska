<?php

declare(strict_types=1);

namespace Statik\Sharing\Share\Processor;

use Statik\Sharing\Share\Data\AbstractData;
use Statik\Sharing\Share\Exception\ShareProcessException;

/**
 * Class AbstractProcessor.
 */
abstract class AbstractProcessor implements ProcessorInterface
{
    protected int $currentBlogId;

    protected int $sourceBlogId;

    protected int $destinationBlogId;

    protected AbstractData $sourceObject;

    protected ?AbstractData $destinationObject;

    /**
     * AbstractProcessor constructor.
     */
    public function __construct(int $sourceBlogId, int $destinationBlogId)
    {
        $this->currentBlogId = \get_current_blog_id();
        $this->sourceBlogId = $sourceBlogId;
        $this->destinationBlogId = $destinationBlogId;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ShareProcessException
     */
    public function share(): bool
    {
        $this->validate();
        $this->validateDuplicates();

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ShareProcessException
     */
    public function delete(): bool
    {
        $this->validate();

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ShareProcessException
     */
    public function detach(): bool
    {
        $this->validate();

        return false;
    }

    /**
     * Get the source blog ID.
     */
    public function getSourceBlogId(): int
    {
        return $this->sourceBlogId;
    }

    /**
     * Get the destination post ID.
     */
    public function getDestinationBlogId(): int
    {
        return $this->destinationBlogId;
    }

    /**
     * Get the Source object.
     */
    public function getSourceObject(): AbstractData
    {
        return $this->sourceObject;
    }

    /**
     * Set the Source object.
     */
    public function setSourceObject(AbstractData $sourceObject): self
    {
        $this->sourceObject = $sourceObject;

        return $this;
    }

    /**
     * Get the Destination object.
     */
    public function getDestinationObject(): ?AbstractData
    {
        return $this->destinationObject;
    }

    /**
     * Set the Destination object.
     */
    public function setDestinationObject(?AbstractData $destinationObject): self
    {
        $this->destinationObject = $destinationObject;

        return $this;
    }

    /**
     * Find duplicate during share process.
     */
    abstract protected function validateDuplicates(): void;

    /**
     * Determinate if all provided data are correct.
     *
     * @throws ShareProcessException
     */
    protected function validate(): void
    {
        if (false === \get_blog_details($this->sourceBlogId)) {
            throw new ShareProcessException(
                \sprintf(\__('The source blog %s does not exist', 'statik'), $this->sourceBlogId)
            );
        }

        if (false === \get_blog_details($this->destinationBlogId)) {
            throw new ShareProcessException(
                \sprintf(\__('The destination blog %s does not exist', 'statik'), $this->destinationBlogId)
            );
        }

        if ($this->sourceBlogId === $this->destinationBlogId) {
            throw new ShareProcessException(\__('Sharing cannot be processed on the same blog', 'statik'));
        }

        /**
         * Fire get custom validate filter.
         *
         * @param string            $validationMessage
         * @param AbstractProcessor $processor
         */
        $customValidation = \apply_filters('Statik\Sharing\getCustomValidate', null, $this);

        if (null !== $customValidation) {
            throw new ShareProcessException(\sprintf(\__('Share validation error: %s', 'statik'), $customValidation));
        }
    }
}
