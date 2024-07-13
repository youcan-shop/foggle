<?php

namespace YouCanShop\Foggle\Drivers;

use Illuminate\Contracts\Events\Dispatcher;
use stdClass;
use YouCanShop\Foggle\Contracts\Driver;
use YouCanShop\Foggle\Contracts\ListsStored;

class ArrayDriver implements Driver, ListsStored
{
    /** @var Dispatcher */
    protected Dispatcher $dispatcher;

    /** @var array<string, (callable(mixed $context): mixed)> */
    protected array $resolvers;

    /** @var array<string, array<string, mixed>> */
    protected array $resolved = [];

    /** @var stdClass */
    protected stdClass $unknown;

    /**
     * @param Dispatcher $dispatcher
     * @param array<string, (callable(mixed $context): mixed)> $resolvers
     */
    public function __construct(Dispatcher $dispatcher, array $resolvers)
    {
        $this->dispatcher = $dispatcher;
        $this->resolvers = $resolvers;

        $this->unknown = new stdClass;
    }

    /**
     * @inheritDoc
     */
    public function defined(): array
    {
        return array_keys($this->resolvers);
    }

    /**
     * @inheritDoc
     */
    public function get(string $name, $context)
    {
        $key = foggle()->serialize($context);

        if (isset($this->resolved[$name][$key])) {
            return $this->resolved[$name][$key];
        }

        return with(
            $this->resolveValue($name, $context),
            function ($value) use ($name, $key) {
                if ($value === $this->unknown) {
                    return false;
                }

                $this->set($name, $key, $value);

                return $value;
            }
        );
    }

    /**
     * @param mixed $context
     *
     * @return mixed
     */
    protected function resolveValue(string $name, $context)
    {
        if (!array_key_exists($name, $this->resolvers)) {
            return $this->unknown;
        }

        return $this->resolvers[$name]($context);
    }

    public function set(string $name, $context, $value): void
    {
        $this->resolved[$name] = $this->resolved[$name] ?? [];
        $this->resolved[$name][foggle()->serialize($context)] = $value;
    }

    /**
     * @inheritDoc
     */
    public function define(string $name, callable $resolver): void
    {
        $this->resolvers[$name] = $resolver;
    }

    public function cFlush(): void
    {
        $this->resolved = [];
    }

    public function stored(): array
    {
        return array_keys($this->resolved);
    }

    public function purge(?array $features): void
    {
        if ($features === null) {
            $this->resolved = [];

            return;
        }

        foreach ($features as $feature) {
            unset($this->resolved[$feature]);
        }
    }
}
