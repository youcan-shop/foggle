<?php

namespace YouCanShop\Foggle\Drivers;

use Illuminate\Contracts\Events\Dispatcher;
use YouCanShop\Foggle\Contracts\Driver;

class ArrayDriver implements Driver
{
    /** @var Dispatcher */
    protected Dispatcher $dispatcher;

    /** @var array<string, (callable(mixed $context): mixed)> */
    protected array $resolvers;

    /**
     * @param Dispatcher $dispatcher
     * @param array<string, (callable(mixed $context): mixed)> $resolvers
     */
    public function __construct(Dispatcher $dispatcher, array $resolvers)
    {
        $this->dispatcher = $dispatcher;
        $this->resolvers = $resolvers;
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
        return $this->resolvers[$name]($context);
    }

    /**
     * @inheritDoc
     */
    public function define(string $name, callable $resolver): void
    {
        $this->resolvers[$name] = $resolver;
    }
}
