<?php

namespace YouCanShop\Foggle\Drivers;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use YouCanShop\Foggle\Contracts\Driver;
use YouCanShop\Foggle\Contracts\Foggable;
use YouCanShop\Foggle\FeatureInteraction;
use YouCanShop\Foggle\Lazily;

/**
 * @mixin FeatureInteraction
 */
class Decorator implements Driver
{
    /** @var Collection<int, array{ name: string, context: mixed, value: mixed}> */
    protected $cache;

    /** @var string */
    private string $name;

    /** @var Driver */
    private Driver $driver;

    /** @var Container */
    private Container $container;

    /**
     * @param string $name
     * @param Driver $driver
     * @param Container $container
     * @param Collection<int, array{ name: string, context: mixed, value: mixed}> $cache
     */
    public function __construct(
        string $name,
        Driver $driver,
        Container $container,
        Collection $cache
    ) {
        $this->name = $name;
        $this->driver = $driver;
        $this->container = $container;
        $this->cache = $cache;
    }

    public function discover(string $namespace = 'App\\Features', ?string $path = null): void
    {
        $namespace = Str::finish($namespace, '\\');

        $entries = (new Finder)->files()->name('*.php')->depth(0)->in($path ?? base_path('app/Features'));

        collect($entries)->each(fn($file) => $this->define("$namespace{$file->getBasename('.php')}"));
    }

    /**
     * @param class-string|string $name
     * @param callable|null $resolver
     *
     * @return void
     */
    public function define(string $name, callable $resolver = null): void
    {
        if ($resolver === null) {
            [$name, $resolver] = [$this->container->make($name)->name ?? $name, new Lazily($name)];
        }

        $this->driver->define($name, function ($context) use ($name, $resolver) {
            if ($resolver instanceof Lazily) {
                $resolver = with(
                    $this->container[$resolver->feature], fn($i) => method_exists($i, 'resolve') ? $i->resolve($context) : $i($context)
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
        $context = $this->parseContext($context);

        $item = $this->cache->whereStrict('context', foggle()->serialize($context))->whereStrict('name', $name)->first();

        if ($item !== null) {
            return $item['value'];
        }

        $value = $this->driver->get($name, $context);
        $this->cPut($name, $context, $value);

        return $value;
    }

    protected function parseContext($context)
    {
        return $context instanceof Foggable ? $context->foggleId() : $context;
    }

    /**
     * @param string $name
     * @param mixed $context
     * @param mixed $value
     *
     * @return void
     */
    protected function cPut(string $name, $context, $value): void
    {
        $key = foggle()->serialize($context);

        $index = $this->cache->search(
            fn($i) => $i['name'] === $name && $i['context'] === $key
        );

        $index === false ? $this->cache[] = ['name' => $name, 'context' => $key, 'value' => $value] : $this->cache[$index] = [
            'name' => $name,
            'context' => $key,
            'value' => $value,
        ];
    }

    public function defined(): array
    {
        return $this->driver->defined();
    }

    public function set(string $name, $context, $value): void
    {
        $context = $this->parseContext($context);
        $this->driver->set($name, $context, $value);

        $this->cPut($name, $context, $value);
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return (new FeatureInteraction($this))->$name(...$arguments);
    }
}
