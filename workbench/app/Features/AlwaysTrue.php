<?php

namespace Workbench\App\Features;

class AlwaysTrue
{
    public function resolve(): bool
    {
        return true;
    }
}
