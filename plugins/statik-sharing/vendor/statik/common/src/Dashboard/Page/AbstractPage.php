<?php

declare(strict_types=1);

namespace Statik\Common\Dashboard\Page;

use Statik\Common\Dashboard\DashboardInterface;

/**
 * Class AbstractPage.
 */
abstract class AbstractPage implements PageInterface
{
    protected DashboardInterface $dashboard;

    /**
     * AbstractPage constructor.
     */
    public function __construct(DashboardInterface $dashboard)
    {
        $this->dashboard = $dashboard;
    }
}
