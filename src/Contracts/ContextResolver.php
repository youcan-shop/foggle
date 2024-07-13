<?php

namespace YouCanShop\Foggle\Contracts;

interface ContextResolver
{
    /**
     * @return mixed
     */
    public function resolve();
}
