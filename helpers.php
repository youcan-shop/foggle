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
