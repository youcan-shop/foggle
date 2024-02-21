<?php

namespace YouCanShop\Foggle\Contracts;

interface Driver
{
    /**
     * @return array<string>
     */
    public function defined(): array;

    /**
     * @param mixed $context
     *
     * @return mixed
     */
    public function get(string $name, $context);

    /**
     * @param string|class-string $name
     * @param (callable(mixed $context): mixed) $resolver
     */
    public function define(string $name, callable $resolver): void;
}
