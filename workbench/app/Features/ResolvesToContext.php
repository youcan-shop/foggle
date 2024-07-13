<?php

namespace Workbench\App\Features;

class ResolvesToContext
{
    public string $name = 'resolves-to-context';

    public function resolve(string $context): bool
    {
        return $context === 'true';
    }
}
