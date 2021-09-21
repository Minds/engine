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
 * @method KeyValueLimiter setThresholds(array $value)
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

    /** @var array */
    private $thresholds = [];

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
     * Returns a consistent record key
     * @return string
     */
    private function getRecordKey(): string
    {
        return "ratelimit:$this->key-$this->value" . $this->seconds ?? ":{$this->seconds}";
    }

    /**
     * Checks and increment the rate limit
     * @return bool
     */
    public function checkAndIncrement(): bool
    {
        if ($this->verifyBypass()) {
            return true;
        }
        $recordKey = $this->getRecordKey();
        $count = (int) $this->getRedis()->get($recordKey);

        if ($count >= $this->max) {
            $this->logger->warn("[RateLimit]: $recordKey was hit with $this->max");
            throw new RateLimitExceededException();
        }

        $this->getRedis()->multi()
            ->incr($recordKey)
            ->expire($recordKey, $this->seconds)
            ->exec();

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
     * Controls rate limit. Can handle multiple thresholds and single thresholds.
     *
     * @param User $user
     * @param string $interaction
     * @return void
     */
    public function control()
    {
        if ($this->verifyBypass()) {
            return true;
        }

        if (count($this->thresholds) > 0) {
            return $this->handleMultiThresholdRateLimit();
        }

        return $this->checkAndIncrement();
    }

    /**
     * Control rate limit for interactions.
     * This creates multiple redis records per rate limit period, per interaction, per user
     *
     * @return void
     */
    private function handleMultiThresholdRateLimit()
    {
        $limits = $this->getLimits();

        // we check all rate limits first in order not to increment the count of a
        // shorter period if we were already ratelimited in a longer period
        foreach ($this->thresholds as $threshold) {
            foreach ($limits as $limit) {
                if ($threshold["period"] === $limit['period'] && ($limit['count'] >= $threshold["threshold"])) {
                    $this->logger->warn("[RateLimit]: {$limit['key']} was hit with {$threshold["threshold"]}");
                    throw new RateLimitExceededException();
                }
            }
        }

        foreach ($this->thresholds as $threshold) {
            $recordKey = $this->getRecordKey() . ":{$threshold["period"]}";
            $this->getRedis()->multi()
                ->incr($recordKey)
                ->expire($recordKey, $threshold["period"])
                ->exec();
        }
    }

    private function getLimits()
    {
        $recordKeys = $this->getRedis()->keys($this->getRecordKey() . ":*"); // e.g. interaction:comment:300
        $counts = $this->getRedis()->mget($recordKeys); // e.g. interaction:comment:300

        $arr = [];

        foreach ($recordKeys as $index => $recordKey) {
            $period = (int) array_reverse(explode(":", $recordKey))[0];
            $count = (int) $counts[$index];

            $arr[] = [
                "key" => $recordKey,
                "period" => $period,
                "count" => $count,
            ];
        }

        return $arr;
    }

    /**
     * Returns the remaining number of attempts a user has
     * @return array
     */
    public function getRemainingAttempts()
    {
        $limits = $this->getLimits();

        $attemps = [];
        // we check all rate limits first in order not to increment the count of a
        // shorter period if we were already ratelimited in a longer period
        foreach ($this->thresholds as $threshold) {
            $count = 0;

            foreach ($limits as $limit) {
                if ($threshold['period'] === $limit['period']) {
                    $count = $limit['count'];
                }
            }

            $attemps[] = [
                "period" => $threshold['period'],
                "remaining" => min($threshold['threshold'] - $count, 0),
            ];
        };

        return $attemps;
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
