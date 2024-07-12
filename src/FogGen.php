<?php

namespace YouCanShop\Foggle;

use InvalidArgumentException;
use YouCanShop\Foggle\Contracts\Foggable;

class FogGen
{
    /**
     * @param string $path The config's dotted path
     *
     * @return callable
     */
    public static function inconfig(string $path): callable
    {
        return function ($context) use ($path) {
            $config = config($path, []);
            if (in_array('*', $config)) {
                return true;
            }

            $context = $context instanceof Foggable ? $context->foggleId() : $context;

            if (!is_string($context)) {
                throw new InvalidArgumentException('Context must be an instance of Foggable or a string');
            }

            return in_array($context, config($path, []));
        };
    }
}
