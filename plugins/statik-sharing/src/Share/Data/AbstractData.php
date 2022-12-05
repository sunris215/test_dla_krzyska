<?php

declare(strict_types=1);

namespace Statik\Sharing\Share\Data;

use Statik\Sharing\Helper\SwitcherTrait;
use Statik\Sharing\Share\Exception\ShareProcessException;

/**
 * Class AbstractData.
 */
abstract class AbstractData implements DataInterface
{
    use SwitcherTrait;

    /** @var mixed */
    protected $object;

    protected int $id;

    protected int $blogId;

    /**
     * SourcePostData constructor.
     *
     * @throws ShareProcessException
     */
    public function __construct(int $id, int $blogId)
    {
        $this->id = $id;
        $this->blogId = $blogId;

        self::switchToBlog($blogId, [$this, 'initialize']);
    }

    /**
     * Get source Post.
     */
    public function getObject(): \WP_Post
    {
        return $this->object;
    }

    /**
     * Get object ID.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get object Blog ID.
     */
    public function getBlogId(): int
    {
        return $this->blogId;
    }

    /**
     * Convert object to array instance.
     */
    public function toArray(): array
    {
        return \get_object_vars($this);
    }

    /**
     * Initialize object data.
     */
    abstract public function initialize(): void;
}
