<?php

declare(strict_types=1);

namespace Statik\Common\Dashboard\Cpt;

/**
 * Class AbstractCpt.
 */
abstract class AbstractCpt implements CptInterface
{
    public const CPT_SLUG = null;

    /**
     * AbstractCpt constructor.
     */
    public function __construct()
    {
        $this->registerCpt();
    }

    /**
     * Register CPT.
     */
    public function registerCpt(): void
    {
        \register_post_type(static::CPT_SLUG, $this->getCptSettings());
    }
}
