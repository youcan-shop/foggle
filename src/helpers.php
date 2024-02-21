<?php

use Illuminate\Contracts\Container\BindingResolutionException;
use YouCanShop\Foggle\Foggle;

if (!function_exists('foggle')) {
    /**
     * @param string|null $feature
     *
     * @return mixed
     * @throws BindingResolutionException
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
