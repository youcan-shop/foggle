<?php

namespace YouCanShop\Foggle;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

class FoggleServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Foggle::class, fn($app) => new Foggle($app));
        $this->mergeConfigFrom(__DIR__ . '/../config/foggle.php', 'foggle');
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [__DIR__ . '/../config/foggle.php' => config_path('foggle.php')],
                'foggle-config'
            );
        }

        $dispatcher = make(Dispatcher::class);

        $dispatcher->listen(
            [\Illuminate\Queue\Events\JobProcessed::class],
            fn() => $this->app[Foggle::class]->cFlush(),
        );

        $dispatcher->listen(
            [\Illuminate\Foundation\Events\PublishingStubs::class],
            fn($e) => $e->add(__DIR__ . '/../stubs/feature.stub', 'feature.stub')
        );
    }
}
