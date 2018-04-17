<?php
/**
 * Redis client
 */
namespace Minds\Core\Data\Redis;

class Client
{

    /** @var Redis */
    private $redis;

    public function __construct()
    {
        $this->redis = new \Redis;
    }

    public function connect(...$args)
    {
        return $this->redis->connect(...$args);
    }

    public function get(...$args)
    {
        return $this->redis->get(...$args);
    }

    public function set(...$args)
    {
        return $this->redis->set(...$args);
    }

    public function delete(...$args)
    {
        return $this->redis->delete(...$args);
    }

    public function __call($function, $arguments)
    {
        return $this->redis->$function(...$arguments);
    }

}
