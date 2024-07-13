<?php

namespace YouCanShop\Foggle;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use RuntimeException;
use YouCanShop\Foggle\Drivers\ArrayDriver;
use YouCanShop\Foggle\Drivers\Decorator;

/**
 * @mixin Decorator
 */
final class Foggle
{
    private Container $container;
    protected array $stores = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param class-string $name
     *
     * @return mixed
     * @throws BindingResolutionException
     */
    public function feature(string $name)
    {
        /** @var object $feature */
        $feature = $this->container->make($name);

        if (!method_exists($feature, 'resolve')) {
            throw new InvalidArgumentException('feature must implement a resolve method');
        }

        return $feature->resolve();
    }

    public function driver(string $name = null): Decorator
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->stores[$name] = $this->stores[$name] ?? $this->resolve($name);
    }

    protected function resolve(string $name): Decorator
    {
        $config = $this->getDriverConfig($name);
        if ($config === null) {
            throw new InvalidArgumentException("Foggle store [$name] is not defined");
        }

        if ($name === 'array') {
            $driver = new ArrayDriver($this->container['events'], []);
        }

        if (!isset($driver)) {
            throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported");
        }

        return new Decorator($name, $driver, $this->container);
    }

    protected function getDriverConfig(string $name): ?array
    {
        return $this->container['config']["foggle.stores.$name"];
    }

    public function getDefaultDriver(): string
    {
        return $this->container['config']->get('foggle.default') ?? 'array';
    }

    public function __call($name, $arguments)
    {
        return $this->driver()->$name(...$arguments);
    }

    public function serialize($context): string
    {
        if ($context === null) {
            return '__foggle_nil';
        }

        if (is_string($context)) {
            return $context;
        }

        if (is_numeric($context)) {
            return (string)$context;
        }

        // Foggables normally get parsed before they reach this part
        throw new RuntimeException('Unable to serialize context, please implement the Foggable contract.');
    }
}
