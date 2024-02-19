<?php

namespace YouCanShop\Foggle\Contracts;

interface Driver
{
    /**
     * @param mixed $context
     *
     * @return mixed
     */
    public function get(string $feature, $context);

    /**
     * @param string $feature
     * @param (callable(mixed $context): mixed) $resolver
     */
    public function define(string $feature, callable $resolver): void;
}
