<?php

namespace YouCanShop\Foggle;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

final class Foggle
{
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
}
