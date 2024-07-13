<?php

namespace YouCanShop\Foggle\Drivers;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\RedisManager;
use stdClass;
use YouCanShop\Foggle\Contracts\Driver;

class RedisDriver implements Driver
{
    protected stdClass $unknown;

    protected string $name;
    protected string $prefix;
    protected Config $config;
    protected RedisManager $redis;
    protected Dispatcher $dispatcher;

    /** @var array<string, (callable(mixed $context): mixed)> */
    protected array $resolvers;

    public function __construct(
        string $name,
        Config $config,
        array $resolvers,
        RedisManager $redis,
        Dispatcher $dispatcher
    ) {
        $this->name = $name;
        $this->redis = $redis;
        $this->config = $config;
        $this->resolvers = $resolvers;
        $this->dispatcher = $dispatcher;

        $this->unknown = new stdClass;
        $this->prefix = $this->config->get("foggle.stores.$this->name.prefix");
    }

    public function defined(): array
    {
        return array_keys($this->resolvers);
    }

    public function get(string $name, $context)
    {
        $result = $this->connection()->command(
            'HGET',
            ["$this->prefix:$name", $context]
        );

        if ($result) {
            return $result;
        }

        return with($this->resolveValue($name, $context), function ($value) use ($name, $context) {
            if ($value === $this->unknown) {
                return false;
            }

//            $this->set($name, $context, $value);

            return $value;
        });
    }

    public function define(string $name, callable $resolver): void
    {
        $this->resolvers[$name] = $resolver;
    }

    public function connection(): Connection
    {
        return $this->redis->connection(
            $this->config->get("foggle.stores.$this->name.connection")
        );
    }

    protected function resolveValue(string $feature, $context)
    {
        if (!array_key_exists($feature, $this->resolvers)) {
            return $this->unknown;
        }

        return $this->resolvers[$feature]($context);
    }
}
