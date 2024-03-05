<?php

namespace YouCanShop\Foggle;

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
            
            return in_array($context, config($path, []));
        };
    }
}
