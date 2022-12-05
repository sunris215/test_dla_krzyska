<?php

declare(strict_types=1);

namespace Statik\Common\Rest;

use Statik\Common\Rest\Controller\AbstractController;
use Statik\Common\Rest\Controller\ControllerInterface;
use Statik\Common\Rest\Controller\V1\Config;

/**
 * Class AbstractApi.
 */
abstract class AbstractApi implements ApiInterface
{
    protected string $namespace;

    /** @var AbstractController[] */
    protected array $registeredControllers = [];

    private static bool $configControllerRegistered = false;

    /**
     * AbstractApi constructor.
     */
    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;

        if (false === self::$configControllerRegistered) {
            $this->registerController(Config::class);
            self::$configControllerRegistered = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function registerController(string $controllerName): ApiInterface
    {
        if (
            false === \array_key_exists($controllerName, $this->registeredControllers)
            && \is_a($controllerName, ControllerInterface::class, true)
        ) {
            /** @var ControllerInterface $object */
            $object = new $controllerName($this);
            $object->registerRoutes();

            $this->registeredControllers[$controllerName] = $object;
        }

        return $this;
    }
}
