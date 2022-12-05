<?php

declare(strict_types=1);

namespace Statik\Sharing\Helper;

/**
 * Class Timer.
 */
class Timer
{
    private array $timers = [];

    private int $count = 0;

    /**
     * Return all timers.
     */
    public function getTimers(): array
    {
        foreach ($this->timers as $name => $times) {
            $timers[$times['id']] = $times + ['duration' => $this->getDuration($name), 'function' => $name];
        }

        return $timers ?? [];
    }

    /**
     * Get duration sum of all timers.
     */
    public function getDurationsSum(): float
    {
        $durationsSum = 0.0;
        foreach ($this->timers as $name => $times) {
            $durationsSum += $this->getDuration($name);
        }

        return $durationsSum;
    }

    /**
     * Start timer.
     */
    public function startTimer(string $name): float
    {
        if (false === isset($this->timers[$name])) {
            $this->timers[$name] = [
                'start'  => \microtime(true),
                'end'    => null,
                'called' => 1,
                'id'     => ++$this->count,
            ];
        } else {
            ++$this->timers[$name]['called'];
        }

        return $this->timers[$name]['start'];
    }

    /**
     * Stop timer.
     */
    public function stopTimer(string $name): ?float
    {
        if (false === isset($this->timers[$name])) {
            return null;
        }

        if ($this->timers[$name]['called'] > 1) {
            --$this->timers[$name]['called'];
        }

        $this->timers[$name]['end'] = \microtime(true);

        return $this->timers[$name]['end'];
    }

    /**
     * Get timer duration.
     */
    public function getDuration(string $name): ?float
    {
        if (false === isset($this->timers[$name])) {
            return null;
        }

        return $this->timers[$name]['end'] - $this->timers[$name]['start'];
    }
}
