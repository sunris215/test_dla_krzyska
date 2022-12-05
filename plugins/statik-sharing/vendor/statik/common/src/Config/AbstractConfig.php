<?php

declare(strict_types=1);

namespace Statik\Common\Config;

use Illuminate\Support\Arr;
use Statik\Common\Config\Driver\DriverInterface;

/**
 * Class AbstractConfig.
 */
abstract class AbstractConfig implements ConfigInterface
{
    protected DriverInterface $driver;

    protected array $config;

    protected bool $changed = false;

    /** @var ConfigInterface[] */
    protected static array $instances = [];

    /**
     * AbstractConfig constructor.
     */
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
        $this->config = $this->driver->getFromSource();
    }

    /**
     * Get instance of a Config.
     */
    public static function Instance(string $namespace, DriverInterface $driver): ConfigInterface
    {
        if (false === isset(self::$instances[$namespace])) {
            self::$instances[$namespace] = new static($driver);
        }

        return self::$instances[$namespace];
    }

    /**
     * @return ConfigInterface[]
     */
    public static function getInstances(): array
    {
        return self::$instances;
    }

    /**
     * {@inheritdoc}
     */
    public function save(): bool
    {
        if (false === $this->changed) {
            return false;
        }

        return $this->driver->setInSource($this->config);
    }

    /**
     * {@inheritdoc}
     */
    public function get(?string $offset, $default = null)
    {
        if (static::isDefaultSettings($offset)) {
            return Arr::get(
                \array_replace_recursive($this->config, static::getDefaultSettings()),
                $offset,
                $default
            );
        }

        return Arr::get($this->config, $offset, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function getKeys(?string $offset): ?array
    {
        $values = $this->get($offset);

        return \is_array($values) ? \array_keys($this->get($offset)) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $offset, $value): bool
    {
        Arr::set($this->config, $offset, $this->filterData($value));

        return $this->changed = true;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $offset): bool
    {
        if (static::isDefaultSettings($offset)) {
            return Arr::has(
                \array_replace_recursive($this->config, static::getDefaultSettings()),
                $offset
            );
        }

        return Arr::has($this->config, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $offset): bool
    {
        $this->config = Arr::except($this->config, $offset);

        return $this->changed = true;
    }

    /**
     * {@inheritdoc}
     */
    public function last(string $offset, $default = null)
    {
        return Arr::last(Arr::get($this->config, $offset), null, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(string $offset, $value, bool $unique = false): bool
    {
        $values = Arr::prepend($this->get($offset, []), $value);

        return $this->set($offset, $unique ? \array_values(\array_unique($values)) : $values);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(bool $flatten = false): array
    {
        $config = \array_replace_recursive($this->config, static::getDefaultSettings());

        return $flatten ? Arr::dot($config) : $config;
    }

    /**
     * Filter data from database and set correct variable type.
     *
     * @param mixed $data
     */
    private function filterData($data)
    {
        switch (\gettype($data)) {
            case 'bool':
                $data = (bool) $data;
                break;
            case 'string':
                $data = \is_numeric($data) ? $data + 0 : \esc_html(\esc_attr((string) $data));
                break;
            case 'array':
                foreach ($data as $key => $item) {
                    $data[$key] = $this->filterData($item);
                }
        }

        return $data;
    }
}
