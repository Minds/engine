<?php
/**
 * Key based limiter
 */
namespace Minds\Core\Security\RateLimits;

use Minds\Traits\MagicAttributes;
use Minds\Core\Di\Di;
use Minds\Core\Data\Redis\Client as RedisServer;
use Minds\Core\Logger;

/**
 * @method KeyValueLimiter setKey(string $key)
 * @method KeyValueLimiter setValue(string $value)
 * @method KeyValueLimiter setMax(int $max)
 * @method KeyValueLimiter setSeconds(int $seconds)
 */
class KeyValueLimiter
{
    use MagicAttributes;

    /** @var RedisServer */
    private $redis;

    /** @var Logger\Log */
    private $logger;

    /** @var string */
    private $key;

    /** @var string */
    private $value;

    /** @var int */
    private $max = 300;

    /** @var int */
    private $seconds = 300; // 5 minutes

    /**
     * @param RedisServer $redis
     * @param Condfig $config
     */
    public function __construct($redis = null, $config = null, $logger = null)
    {
        $config = $config ?? Di::_()->get('Config');
        $this->redis = $redis ?: new RedisServer();
        $this->redis->connect($config->redis['master']);
        $this->logger = $logger ?? Di::_()->get('Logger');
    }

    /**
     * Checks and increment the rate limit
     * @return bool
     */
    public function checkAndIncrement(): bool
    {
        $recordKey = "ratelimit:$this->key-$this->value:$this->seconds";
        $count = (int) $this->redis->get("$recordKey");
        
        if ($count >= $this->max) {
            $this->logger->warn("[RateLimit]: $recordKey was hit with $this->max");
            throw new RateLimitExceededException();
        }

        $this->redis->multi()
            ->incr($recordKey)
            ->expire($recordKey, $this->seconds)
            ->exec();

        return true;
    }
}
