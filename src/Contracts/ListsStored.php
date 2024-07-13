<?php

namespace YouCanShop\Foggle\Contracts;

interface ListsStored
{
    /**
     * Retrieve the names of all stored features.
     *
     * @return array<string>
     */
    public function stored(): array;
}
