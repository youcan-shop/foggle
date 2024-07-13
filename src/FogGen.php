<?php

namespace YouCanShop\Foggle;

use InvalidArgumentException;
use YouCanShop\Foggle\Contracts\Foggable;

class FogGen
{
    /**
     * @param string $path The config's dotted path
     * @param string $separator
     *
     * @return callable
     */
    public static function inconfig(string $path, string $separator = ','): callable
    {
        return function ($context) use ($path, $separator) {
            $config = config($path, []);

            if (is_string($config)) {
                $config = splode($config, $separator);
            }

            if (in_array('*', $config)) {
                return true;
            }

            $context = $context instanceof Foggable ? $context->foggleId() : $context;

            if (!is_string($context)) {
                throw new InvalidArgumentException('Context must be an instance of Foggable or a string');
            }

            return in_array($context, $config);
        };
    }
}
