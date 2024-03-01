<?php

namespace Workbench\App\Features;

class ResolvesToContext
{
    public string $name = 'resolves-to-context';

    public function resolve(bool $context): bool
    {
        return $context;
    }
}
