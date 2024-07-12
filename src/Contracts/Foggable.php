<?php

namespace YouCanShop\Foggle\Contracts;

/**
 * This is a helper for FogGen generations allowing you to pass the entire context
 * e.g. foggle()->for($store) instead of foggle()->for($store->getId())
 */
interface Foggable
{
    public function foggleId(): string;
}
