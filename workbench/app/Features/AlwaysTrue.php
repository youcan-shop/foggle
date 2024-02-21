<?php

namespace Workbench\App\Features;

class AlwaysTrue
{
    public string $name = 'always-true';

    public function resolve(): bool
    {
        return true;
    }
}
