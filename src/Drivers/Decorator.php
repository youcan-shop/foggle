<?php

namespace YouCanShop\Foggle\Drivers;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use YouCanShop\Foggle\Contracts\Driver;
use YouCanShop\Foggle\Lazily;

class Decorator implements Driver
{
    private string $name;

    private Driver $driver;

    private Container $container;

    public function __construct(
        string $name,
        Driver $driver,
        Container $container
    ) {
        $this->name = $name;
        $this->driver = $driver;
        $this->container = $container;
    }

    /**
     * @throws BindingResolutionException
     */
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
     * @param class-string|string $name
     * @param callable|null $resolver
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function define(string $name, callable $resolver = null): void
    {
        if ($resolver === null) {
            [$name, $resolver] = [$this->container->make($name)->name ?? $name, new Lazily($name)];
        }

        $this->driver->define($name, function ($context) use ($name, $resolver) {
            if ($resolver instanceof Lazily) {
                $resolver = with(
                    $this->container[$resolver->feature],
                    fn($i) => method_exists($i, 'resolve')
                        ? $i->resolve($context)
                        : $i($context)
                );
            }

            if (!$resolver instanceof Closure) {
                return $this->resolve($name, fn() => $resolver, $context);
            }

            return $this->resolve($name, $resolver, $context);
        });
    }

    /**
     * @param string $name
     * @param callable $resolver
     * @param mixed $context
     *
     * @return mixed
     */
    protected function resolve(string $name, callable $resolver, $context)
    {
        return $resolver($context);
    }

    public function get(string $name, $context)
    {
        return $this->driver->get($name, $context);
    }

    public function defined(): array
    {
        return $this->driver->defined();
    }
}
