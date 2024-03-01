<?php

namespace YouCanShop\Foggle;

use Illuminate\Support\Collection;
use YouCanShop\Foggle\Drivers\Decorator;

class FeatureInteraction
{
    protected Decorator $driver;
    protected array $context = [];

    public function __construct(Decorator $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @param mixed $scope
     */
    public function for($scope): self
    {
        $this->context = array_merge(
            $this->context,
            Collection::wrap($scope)->all()
        );

        return $this;
    }

    /**
     * @return mixed
     */
    public function value(string $feature)
    {
        return $this->driver->get($feature, $this->context()[0]);
    }

    public function active(string $feature): bool
    {
        return Collection::make($feature)
            ->crossJoin($this->context())
            ->every(fn($args) => $this->driver->get(...$args) !== false);
    }

    public function inactive(string $feature): bool
    {
        return !$this->active($feature);
    }

    /**
     * @return array
     */
    protected function context(): array
    {
        return $this->context ?: [null];
    }
}
