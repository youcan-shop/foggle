<?php

namespace YouCanShop\Foggle\Drivers;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionFunction;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use YouCanShop\Foggle\Contracts\Driver;
use YouCanShop\Foggle\Contracts\ListsStored;
use YouCanShop\Foggle\FeatureInteraction;
use YouCanShop\Foggle\Lazily;

/**
 * @mixin FeatureInteraction
 */
class Decorator implements Driver, ListsStored
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
     * @param class-string|string $name The feature's name
     * @param class-string|null $type The context resolver's type
     */
    public function define(string $name, ?callable $resolver = null, ?string $type = null): void
    {
        if ($resolver === null) {
            $feature = $this->container->make($name);
            [$name, $resolver, $type] = [
                $feature->name ?? $name,
                new Lazily($name),
                $feature->contextType ?? null,
            ];
        }

        $this->driver->define($name, function ($context) use ($name, $resolver, $type) {
            if ($context === null && $type !== null) {
                $context = foggle()->resolveContext($type);
            }

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

            if ($context !== null) {
                return $this->resolve($name, $resolver, $context);
            }

            if ($this->canHandleNullContext($resolver)) {
                return $this->resolve($name, $resolver, $context);
            }

            return $this->resolve($name, fn() => false, $context);
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

    protected function canHandleNullContext(callable $resolver): bool
    {
        $function = new ReflectionFunction(Closure::fromCallable($resolver));

        return $function->getNumberOfParameters() === 0
            || $function->getParameters()[0]->hasType()
            || $function->getParameters()[0]->getType()->allowsNull();
    }

    /**
     * @return mixed
     */
    public function get(string $name, $context)
    {
        $key = foggle()->serialize($context);

        $item = $this->cache
            ->whereStrict('context', foggle()->serialize($key))
            ->whereStrict('name', $name)
            ->first();

        if ($item !== null) {
            return $item['value'];
        }

        $value = $this->driver->get($name, $context);
        $this->cPut($name, $key, $value);

        return $value;
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
        $key = foggle()->serialize($context);

        $this->driver->set($name, $key, $value);
        $this->cPut($name, $key, $value);
    }

    public function cFlush(): void
    {
        $this->cache = collect();
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

    public function stored(): array
    {
        if (!$this->driver instanceof ListsStored) {
            throw new RuntimeException("The [$this->name] driver does not support listing stored features.");
        }

        return $this->driver->stored();
    }

    public function purge(?array $features): void
    {
        if ($features === null) {
            $this->driver->purge(null);
            $this->cache = collect();

            return;
        }

        Collection::wrap($features)
            ->map(Closure::fromCallable([$this, 'resolve']))
            ->pipe(
                function ($features) {
                    $this->driver->purge($features->all());

                    $this->cache->forget(
                        $this->cache
                            ->whereInStrict('feature', $features)
                            ->keys()
                            ->all()
                    );
                }
            );
    }
}
