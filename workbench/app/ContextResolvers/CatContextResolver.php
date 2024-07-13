<?php

namespace Workbench\App\ContextResolvers;

use Workbench\App\Models\Cat;
use YouCanShop\Foggle\Contracts\ContextResolver;

class CatContextResolver implements ContextResolver
{
    public function resolve(): Cat
    {
        return new Cat('7');
    }
}
