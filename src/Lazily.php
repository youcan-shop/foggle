<?php

namespace YouCanShop\Foggle;

class Lazily
{
    public string $feature;

    public function __construct(string $feature)
    {
        $this->feature = $feature;
    }
}
