<?php

declare(strict_types=1);

namespace Statik\Sharing\Integration;

/**
 * Class IntegrationManager.
 */
class IntegrationManager
{
    /** @var IntegratorInterface[] */
    private array $registeredIntegrators = [];

    /**
     * Register plugin integrator.
     */
    public function registerIntegration(string ...$classes): self
    {
        foreach ($classes as $class) {
            if (\array_key_exists($class, $this->registeredIntegrators)) {
                continue;
            }

            $this->registeredIntegrators[$class] = null;
        }

        return $this;
    }

    /**
     * Initialize plugin integrators.
     */
    public function initIntegrations(): void
    {
        foreach ($this->registeredIntegrators as $namespace => $block) {
            if (false === \is_a($namespace, IntegratorInterface::class, true)) {
                continue;
            }

            if (null !== $block) {
                continue;
            }

            if (false === $namespace::canBeEnabled()) {
                continue;
            }

            /** @var IntegratorInterface $pluginIntegrator */
            $pluginIntegrator = new $namespace();

            $this->registeredIntegrators[$namespace] = $pluginIntegrator;
        }
    }

    /**
     * Determinate if Integration is enabled.
     */
    public function isEnabled(string $class): bool
    {
        if (false === isset($this->registeredIntegrators[$class])) {
            return false;
        }

        return $this->registeredIntegrators[$class]->canBeEnabled();
    }
}
