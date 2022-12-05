<?php

declare(strict_types=1);

namespace Statik\Common\Cli;

/**
 * Interface CommandManagerInterface.
 */
interface CommandManagerInterface
{
    /**
     * Get command name.
     */
    public function getCommandName(): string;

    /**
     * Register command in WP CLI.
     */
    public function registerCommand(string $commandClass): self;
}
