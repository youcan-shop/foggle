<?php

namespace Workbench\App\Features;

use Workbench\App\Models\Cat;

class AllowNumberSeven
{
    public string $name = 'allow-number-seven';
    public string $contextType = Cat::class;

    public function resolve(Cat $cat): bool
    {
        return $cat->foggleId() === '7';
    }
}
