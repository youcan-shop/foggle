<?php

namespace Workbench\App\Models;

use YouCanShop\Foggle\Contracts\Foggable;

class User implements Foggable
{
    private string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function foggleId(): string
    {
        return $this->id;
    }
}
