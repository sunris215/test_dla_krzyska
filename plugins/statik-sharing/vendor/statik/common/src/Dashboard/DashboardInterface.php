<?php

declare(strict_types=1);

namespace Statik\Common\Dashboard;

/**
 * Interface DashboardInterface.
 */
interface DashboardInterface
{
    /**
     * Register page.
     */
    public function registerPage(string $pageClassName): self;

    /**
     * Register Custom Post Type.
     */
    public function registerCpt(string $cptClassName): self;
}
