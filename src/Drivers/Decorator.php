<?php

namespace YouCanShop\Foggle\Drivers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Str;
use stdClass;
use Symfony\Component\Finder\Finder;
use YouCanShop\Foggle\Contracts\Driver;
use YouCanShop\Foggle\Lazily;

class Decorator implements Driver
{
    private string $name;

    private Driver $driver;

    private Container $container;

    /** @var callable */
    private $resolver;

    public function __construct(
        string $name,
        Driver $driver,
        callable $resolver,
        Container $container
    ) {
        $this->name = $name;
        $this->driver = $driver;
        $this->resolver = $resolver;
        $this->container = $container;
    }

    public function discover(string $namespace = 'App\\Features', ?string $path = null): void
    {
        $namespace = Str::finish($namespace, '\\');

        $entries = (new Finder)
            ->files()
            ->name('*.php')
            ->depth(0)
            ->in($path ?? base_path('app/Features'));

        collect($entries)->each(fn($file) => $this->define("$namespace{$file->getBasename('.php')}"));
    }

    /**
     * @param class-string $name
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function define(string $name)
    {
        /** @var stdClass $instance */
        $instance = $this->container->make($name);
        $feature = $instance->name ?? class_basename($name);

        $this->driver->define($feature, function ($context) use ($feature, $instance) {
            $resolver = fn () => $instance->resolve($context);
        });
    }

    public function get(string $feature, $context)
    {
        // TODO: Implement get() method.
    }
}
