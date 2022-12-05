<?php

declare(strict_types=1);

namespace Statik\Common\Helper;

/**
 * Class Callback.
 */
class Callback
{
    /**
     * Helper for generate Array from callback.
     *
     * @param mixed $callback
     */
    public static function getResults($callback)
    {
        if (false === \is_array($callback)) {
            return \is_callable($callback) ? $callback() : $callback;
        }

        if (
            static::isNotAssocArray($callback)
            && (\is_object($callback[0]) || \class_exists($callback[0]))
            && \method_exists($callback[0], $callback[1])
        ) {
            $callback = \call_user_func_array([$callback[0], $callback[1]], \array_slice($callback, 2));
        }

        return $callback;
    }

    /**
     * Check if array is associative.
     */
    private static function isNotAssocArray(array $array): bool
    {
        if ([] === $array) {
            return false;
        }

        return \array_keys($array) === \range(0, \count($array) - 1);
    }
}
