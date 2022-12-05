<?php

declare(strict_types=1);

namespace Statik\Common\Helper;

/**
 * Class Benchmark.
 */
class Benchmark
{
    /**
     * Compare functions. Allow input many functions and run the specified number
     * of times. As result are getting summary, average and per second times.
     */
    public static function compare(int $iterations, callable ...$functions): void
    {
        $results = ['iterations' => $iterations];

        foreach ($functions as $key => $function) {
            $results["function {$key}"]['sum'] = 0.0;
            for ($i = 0; $i < $iterations; ++$i) {
                \ob_start();
                $start = \microtime(true);
                $function();
                $end = \microtime(true) - $start;
                \ob_get_clean();
                $results["function {$key}"]['sum'] += $end;
            }
            $results["function {$key}"]['average'] = $results["function {$key}"]['sum'] / $iterations;
            $results["function {$key}"]['per_second'] = 1 / $results["function {$key}"]['average'];
        }

        /* @noinspection ForgottenDebugOutputInspection */
        \dump($results ?? []);
    }
}
