<?php

/**
 * Key based limiter
 */

namespace Minds\Core\Security\RateLimits;

use Minds\Common\Jwt;
use Minds\Traits\MagicAttributes;
use Minds\Core\Di\Di;
use Minds\Core\Data\Redis\Client as RedisServer;
use Minds\Core\Logger;

/**
 * @method KeyValueLimiter setKey(string $key)
 * @method KeyValueLimiter setValue(string $value)
 * @method KeyValueLimiter setMax(int $max)
 * @method KeyValueLimiter setSeconds(int $seconds)
 * @method KeyValueLimiter setRateLimits(RateLimit[] $ratelimits)
 */
class KeyValueLimiter
{
    use MagicAttributes;

    /** @var Config */
    private $config;

    /** @var RedisServer */
    private $redis;

    /** @var bool */
    private $redisIsConnected = false;

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

    /** @var Jwt */
    protected $jwt;

    /** @var RateLimit[] */
    private $rateLimits;

    /**
     * @param RedisServer $redis
     * @param Condfig $config
     */
    public function __construct($redis = null, $config = null, $logger = null, $jwt = null)
    {
        $this->config = $config ?? Di::_()->get('Config');
        $this->redis = $redis ?: new RedisServer();
        $this->logger = $logger ?? Di::_()->get('Logger');
        $this->jwt = $jwt ?? new Jwt();
        $this->jwt->setKey($this->config->get('cypress')['shared_key'] ?? '');
    }

    /**
     * Returns a consistent record key based on a rateLimit
     *
     * @param RateLimit $rateLimit
     * @return string
     */
    private function getRecordKey($rateLimit): string
    {
        $resetPeriod = round(time() / $rateLimit->getSeconds());
        return "ratelimit:{$rateLimit->getKey()}-$this->value:{$rateLimit->getSeconds()}:{$resetPeriod}";
    }

    /**
     * checks ratelimits and throws an exception if one was hit
     *
     * @throws RateLimitExceededException
     * @return void
     */
    private function check()
    {
        if ($this->verifyBypass()) {
            return true;
        }

        $keys = array_map(fn ($rateLimit): string => $this->getRecordKey($rateLimit), $this->getRateLimits());
        $counts = $this->getRedis()->mget($keys);

        foreach ($this->getRateLimits() as $index => $rateLimit) {
            $max = $rateLimit->getMax();
            $count = (int) $counts[$index];

            if ($count >= $max) {
                $this->logger->warn("[RateLimit]: {$rateLimit->getKey()} was hit with $max");
                throw new RateLimitExceededException();
            }
        }
    }

    /**
     * checks ratelimits and throws an exception if one was hit
     *
     * @return RateLimit[] $rateLimits
     */
    private function getRemainingAttempts()
    {
        $keys = array_map(fn ($rateLimit): string => $this->getRecordKey($rateLimit), $this->getRateLimits());
        $counts = $this->getRedis()->mget($keys);

        $rateLimits = [];
        foreach ($this->getRateLimits() as $index => $rateLimit) {
            $rateLimit->setRemaining(min($rateLimit->getMax() - (int) $counts[$index], 0));

            $rateLimits[] = $rateLimit;
        }

        return $rateLimits;
    }

    /**
     * increments count and resets expiry of rateLimits
     *
     * @return void
     */
    private function increment()
    {
        foreach ($this->getRateLimits() as $rateLimit) {
            $recordKey = $this->getRecordKey($rateLimit);
            $this->getRedis()->multi()
                ->incr($recordKey)
                ->expire($recordKey, $rateLimit->getSeconds())
                ->exec();
        }
    }

    /**
     * Checks and increments the rate limit
     *
     * @return bool
     */
    public function checkAndIncrement(): bool
    {
        $this->check();
        $this->increment();

        return true;
    }

    /**
     * Verify whether or not rate limits can be bypassed.
     * @return bool
     */
    public function verifyBypass(): bool
    {
        if (!isset($_COOKIE['rate_limit_bypass'])) {
            return false;
        }
        try {
            $this->logger->warn('[KVLimiter]: Bypass cookie was used');

            $decoded = $this->jwt->decode($_COOKIE['rate_limit_bypass']);
            $timeDiff = time() / ($decoded['timestamp_ms'] / 1000);

            // if less than 5 minutes.
            return $timeDiff < 300;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Returns rate limits. Supports legacy seconds and max
     *
     * @return RateLimit[]
     */
    private function getRateLimits()
    {
        $rateLimits = $this->rateLimits;

        // legacy support
        if ($this->max && $this->seconds) {
            $rateLimit = new RateLimit();
            $rateLimit->setSeconds($this->seconds);
            $rateLimit->setMax($this->max);
            $rateLimit->setKey($this->key);

            $rateLimits[] = $rateLimit;
        }

        return $rateLimits;
    }

    /**
     * Get our redis connection
     * @return RedisServer
     */
    private function getRedis(): RedisServer
    {
        if (!$this->redisIsConnected && $this->config->redis) {
            $this->redis->connect($this->config->redis['master']);
            $this->redisIsConnected = true;
        }
        return $this->redis;
    }
}
