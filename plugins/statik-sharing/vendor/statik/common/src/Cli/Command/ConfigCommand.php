<?php

declare(strict_types=1);

namespace Statik\Common\Cli\Command;

use Statik\Common\Cli\CommandManagerInterface;
use Statik\Common\Config\AbstractConfig;
use Statik\Common\Config\ConfigInterface;
use WP_CLI\ExitException;

/**
 * Manage Statik plugin Configuration.
 */
class ConfigCommand extends AbstractCommand
{
    /** @var ConfigInterface[] */
    private array $configs;

    /**
     * ConfigCommand constructor.
     *
     * @throws \Exception
     */
    public function __construct(CommandManagerInterface $commandManager)
    {
        parent::__construct($commandManager);
        $this->configs = AbstractConfig::getInstances();

        /* @noinspection PhpParamsInspection */
        \WP_CLI::add_command($this->commandManager->getCommandName() . ' config', $this);
    }

    /**
     * List all values defined in Config.
     *
     * ## OPTIONS
     *
     * <config>
     * : Name of Config.
     *
     * @throws ExitException
     */
    public function list(array $args): void
    {
        $config = $args[0];
        $results = [];

        if (false === \array_key_exists($config, $this->configs)) {
            \WP_CLI::error("The Config '{$config}' is not defined.", true);
        }

        foreach ($this->configs[$config]->toArray(true) as $fieldName => $fieldValue) {
            $results[] = [
                'name'  => $fieldName,
                'value' => \is_bool($fieldName) ? (int) $fieldValue : $fieldValue,
            ];
        }

        \WP_CLI\Utils\format_items('table', $results ?? [], ['name', 'value']);
    }

    /**
     * Gets the value of a specific key from Config.
     *
     * ## OPTIONS
     *
     * <config>
     * : Name of Config.
     *
     * <name>
     * : Name of the Config field.
     *
     * @throws ExitException
     */
    public function get(array $args): void
    {
        [$config, $name] = $args;

        if (false === \array_key_exists($config, $this->configs)) {
            \WP_CLI::error("The Config '{$config}' is not defined.", true);
        }

        if ($this->configs[$config]->has($name)) {
            $value = $this->configs[$config]->get($name);

            \WP_CLI::line(\is_bool($value) ? (int) $value : $value);
        } else {
            \WP_CLI::error("The value '{$name}' is not defined in the '{$config}' Config.", true);
        }
    }

    /**
     * Checks whether a specific value exists in Config.
     *
     * ## OPTIONS
     *
     * <config>
     * : Name of Config.
     *
     * <name>
     * : Name of the Config field.
     *
     * @throws ExitException
     */
    public function has(array $args): void
    {
        [$config, $key] = $args;

        if (false === \array_key_exists($config, $this->configs)) {
            \WP_CLI::error("The Config '{$config}' is not defined.", true);
        }

        \WP_CLI::line($this->configs[$config]->has($key) ? 1 : 0);
    }

    /**
     * Sets the value of a specific key in Config.
     *
     * ## OPTIONS
     *
     * <config>
     * : Name of Config.
     *
     * <name>
     * : Name of the Config field.
     *
     * <value>
     * : Value to set in Config.
     *
     * @throws ExitException
     */
    public function set(array $args): void
    {
        [$config, $key, $value] = $args;

        if (false === \array_key_exists($config, $this->configs)) {
            \WP_CLI::error("The Config '{$config}' is not defined.", true);
        }

        if (false === $this->configs[$config]->set($key, $value)) {
            \WP_CLI::error("The value '{$key}' is overridden by constant or is not a scalar type.", true);
        }

        $this->configs[$config]->save();

        \WP_CLI::success("Added the value '{$key}' to the Config '{$config}' with the value '{$value}'.");
    }

    /**
     * Delete the value of a specific key in Config.
     *
     * ## OPTIONS
     *
     * <config>
     * : Name of Config.
     *
     * <name>
     * : Name of the Config field.
     *
     * @throws ExitException
     */
    public function delete(array $args): void
    {
        [$config, $key] = $args;

        if (false === \array_key_exists($config, $this->configs)) {
            \WP_CLI::error("The Config '{$config}' is not defined.", true);
        }

        if (false === $this->configs[$config]->has($key)) {
            \WP_CLI::error("The value '{$key}' is not defined in the Config '{$config}'.", true);
        }

        $this->configs[$config]->delete($key);
        $this->configs[$config]->save();

        \WP_CLI::success("Success: Deleted the value '{$key}' from the Config '{$config}'.");
    }
}
