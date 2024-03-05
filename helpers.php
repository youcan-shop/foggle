<?php

use YouCanShop\Foggle\Foggle;

if (!function_exists('foggle')) {
    /**
     * @param string|null $feature
     *
     * @return Foggle|mixed
     */
    function foggle(string $feature = null)
    {
        $foggle = make(Foggle::class);

        switch (func_num_args()) {
            case 1:
                return $foggle->get($feature, 0);
            default:
                return $foggle;
        }
    }
}

if (!function_exists('splode')) {
    /**
     * @return false|string[]
     */
    function splode(?string $string, string $separator = ',', int $limit = PHP_INT_MAX)
    {
        if (empty($string)) {
            return [];
        }

        return explode($separator, $string, $limit);
    }
}
