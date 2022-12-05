<?php

declare(strict_types=1);

namespace Statik\Common\Cli\Command;

use Statik\Common\Cli\CommandManagerInterface;

/**
 * Class AbstractCommand.
 */
abstract class AbstractCommand implements CommandInterface
{
    protected ?CommandManagerInterface $commandManager;

    /**
     * AbstractCommand constructor.
     */
    public function __construct(CommandManagerInterface $commandManager = null)
    {
        $this->commandManager = $commandManager;
    }
}
