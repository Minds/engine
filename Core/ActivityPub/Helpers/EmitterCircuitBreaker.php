<?php
declare(strict_types=1);

namespace Minds\Core\ActivityPub\Helpers;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class EmitterCircuitBreaker
{
    private const CACHE_PREFIX = "activitypub:circuit-breaker:";

    public function __construct(
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @param string $target
     * @return CircuitStatusEnum
     * @throws InvalidArgumentException
     */
    public function evaluateCircuit(string $target): CircuitStatusEnum
    {
        $targetCircuitDetails = $this->cache->get(self::CACHE_PREFIX . $target);
        if (!$targetCircuitDetails) {
            return CircuitStatusEnum::HEALTHY;
        }

        $targetCircuitDetails = json_decode($targetCircuitDetails, true);

        if (strtotime("+" . (10 * $targetCircuitDetails['failures']) . " seconds", $targetCircuitDetails['lastFailureTimestamp']) < time()) {
            return CircuitStatusEnum::HEALTHY;
        }

        return $targetCircuitDetails['failures'] <= 5 ? CircuitStatusEnum::OVERLOADED : CircuitStatusEnum::UNHEALTHY;
    }

    /**
     * @param string $target
     * @return void
     * @throws InvalidArgumentException
     */
    public function tripCircuit(string $target): void
    {
        $targetCircuitDetails = $this->cache->get(self::CACHE_PREFIX . $target);
        if (!$targetCircuitDetails) {
            $targetCircuitDetails = [
                'failures' => 0,
                'lastFailureTimestamp' => time(),
            ];
        } else {
            $targetCircuitDetails = json_decode($targetCircuitDetails, true);
            $targetCircuitDetails['failures']++;
            $targetCircuitDetails['lastFailureTimestamp'] = time();
        }

        $this->cache->set(self::CACHE_PREFIX . $target, json_encode($targetCircuitDetails), 30 + (10 * $targetCircuitDetails['failures']));
    }
}

enum CircuitStatusEnum
{
    case HEALTHY;
    case OVERLOADED;
    case UNHEALTHY;
}
