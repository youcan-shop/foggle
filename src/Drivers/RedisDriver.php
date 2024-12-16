<?php

namespace YouCanShop\Foggle\Drivers;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\RedisManager;
use stdClass;
use YouCanShop\Foggle\Contracts\Driver;
use YouCanShop\Foggle\Contracts\ListsStored;
use YouCanShop\Foggle\Utilities\Redis;

class RedisDriver implements Driver, ListsStored
{
    protected stdClass $unknown;

    protected string $name;
    protected string $prefix;
    protected RedisManager $redis;
    protected Config $config;
    protected Dispatcher $dispatcher;

    /** @var array<string, (callable(mixed $context): mixed)> */
    protected array $resolvers;

    public function __construct(
        string $name,
        array $resolvers,
        RedisManager $redis,
        Config $config,
        Dispatcher $dispatcher
    ) {
        $this->name = $name;
        $this->resolvers = $resolvers;
        $this->redis = $redis;
        $this->config = $config;
        $this->dispatcher = $dispatcher;

        $this->unknown = new stdClass;
        $this->prefix = $this->config->get("foggle.stores.$this->name.prefix");
    }

    public function get(string $name, $context)
    {
        $key = foggle()->serialize($context);

        $result = $this->connection()->command(
            'HGET',
            ["$this->prefix:$name", $key]
        );

        if ($result) {
            return unserialize($result);
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

    protected function connection(): Connection
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

    public function set(string $name, $context, $value): void
    {
        if ($context) {
            $key = foggle()->serialize($context);
            $this->connection()->command('HSET', ["$this->prefix:$name", $key, serialize($value)]);
        }
    }

    public function defined(): array
    {
        return array_keys($this->resolvers);
    }

    public function define(string $name, callable $resolver): void
    {
        $this->resolvers[$name] = $resolver;
    }

    public function stored(): array
    {
        $keys = $this->redis->eval(Redis::GET_ALL, 1, "$this->prefix:*");

        return array_map(fn($key) => substr(strrchr($key, ':'), 1), $keys);
    }

    public function purge(?array $features): void
    {
        if ($features === null) {
            $this->redis->eval(Redis::PURGE, 1, "$this->prefix:*");
        } else {
            $this->redis->pipeline(function ($pipe) use ($features) {
                foreach ($features as $feature) {
                    $pipe->del("$this->prefix:$feature");
                }
            });
        }
    }
}
