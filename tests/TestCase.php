<?php

namespace Tests;

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
    //
}
