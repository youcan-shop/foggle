<?php

namespace Tests;

use Illuminate\Contracts\Config\Repository;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
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
                'billing' => [
                    'sellers' => splode('1,2,3'),
                ],
            ]);
        });
    }
}
