<?php

namespace Tests;

use Illuminate\Contracts\Config\Repository;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Workbench\App\ContextResolvers\CatContextResolver;
use Workbench\App\Models\Cat;
use YouCanShop\Foggle\Foggle;

abstract class TestCase extends OrchestraTestCase
{
    use WithWorkbench;

    public function foggle(): Foggle
    {
        return new Foggle($this->app);
    }

    protected function defineEnvironment($app)
    {
        tap($app['config'], function (Repository $config) {
            $config->set('features', [
                'billing'            => [
                    'sellers' => '1,2,3',
                ],
                'allow-number-seven' => '7',
            ]);

            $config->set('foggle.context_resolvers', [
                Cat::class => CatContextResolver::class,
            ]);
        });
    }
}
