<?php
/**
 * Redis Pub/Sub Client interface
 */
namespace Minds\Core\Data\PubSub\Redis;

use Minds\Core\Config;

class Client
{
    /**
     * @var \Redis
     */
    private $redis;
    /**
     * @var string
     */
    private $host;

    public function __construct($redis = null)
    {
        if (class_exists('\Redis')) {
            $this->redis = $redis ?: new \Redis();
            $config = Config::_()->get('redis');
            $this->host = ($config['pubsub'] ?? null) ?: $config['master'] ?: '127.0.0.1';
            $this->connect();
        }
    }

    /**
     * @throws \Exception
     */
    public function connect(): void
    {
        if (!$this->redis instanceof \Redis) {
            $this->redis = new \Redis();
        }

        if (!$this->redis->isConnected()) {
            if (!@$this->redis->connect($this->host)) {
                throw new \Exception("Unable to connect to Redis: " . $this->redis->getLastError());
            }
        }
    }

    public function __destruct()
    {
        try {
            if ($this->redis) {
                $this->redis->close();
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * @param $channel
     * @param string $data
     * @return bool|int
     */
    public function publish($channel, $data = '')
    {
        if (!$this->redis->isConnected()) {
            return false;
        }

        return $this->redis->publish($channel, $data);
    }
}
