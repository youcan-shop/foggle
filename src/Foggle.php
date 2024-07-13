<?php

namespace YouCanShop\Foggle;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use RuntimeException;
use YouCanShop\Foggle\Contracts\ContextResolver;
use YouCanShop\Foggle\Contracts\Foggable;
use YouCanShop\Foggle\Drivers\ArrayDriver;
use YouCanShop\Foggle\Drivers\Decorator;
use YouCanShop\Foggle\Drivers\RedisDriver;

/**
 * @mixin Decorator
 */
final class Foggle
{
    /** @var array<Decorator> */
    protected array $stores = [];

    /** @var array<ContextResolver> */
    protected array $contextResolvers = [];

    private Container $container;

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

    protected function resolve(string $name): Decorator
    {
        $config = $this->getDriverConfig($name);
        if ($config === null) {
            throw new InvalidArgumentException("Foggle store [$name] is not defined");
        }

        if ($name === 'array') {
            $driver = new ArrayDriver($this->container['events'], []);
        }

        if ($name === 'redis') {
            $driver = new RedisDriver(
                $name,
                [],
                $this->container['config'],
                $this->container['redis'],
                $this->container['events']
            );
        }

        if (!isset($driver)) {
            throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported");
        }

        return new Decorator($name, $driver, $this->container, collect());
    }

    protected function getDriverConfig(string $name): ?array
    {
        return $this->container['config']["foggle.stores.$name"];
    }

    /**
     * @return mixed
     */
    public function resolveContext(string $name)
    {
        if (!isset($this->contextResolvers[$name])) {
            $fqn = $this->container['config']["foggle.context_resolvers.$name"];
            if ($fqn === null || !is_a($fqn, ContextResolver::class, true)) {
                throw new InvalidArgumentException("Context resolver for '$name' not found");
            }

            /** @var class-string $fqn */
            $this->contextResolvers[$name] = $this->container[$fqn];
        }

        return $this->contextResolvers[$name]->resolve();
    }

    public function __call($name, $arguments)
    {
        return $this->driver()->$name(...$arguments);
    }

    public function driver(string $name = null): Decorator
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->stores[$name] = $this->stores[$name] ?? $this->resolve($name);
    }

    public function getDefaultDriver(): string
    {
        return $this->container['config']->get('foggle.default') ?? 'array';
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

        if ($context instanceof Foggable) {
            return $context->foggleId();
        }

        // Foggables normally get parsed before they reach this part
        throw new RuntimeException('Unable to serialize context, please implement the Foggable contract.');
    }

    public function cFlush(): void
    {
        foreach ($this->stores as $driver) {
            $driver->cFlush();
        }

        if (isset($this->stores['array'])) {
            $this->stores['array']->getDriver()->cFlush();
        }
    }
}
