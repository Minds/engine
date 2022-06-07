<?php
/**
 * Features and experiments via signed cookies via cypress
 * @author mark
 */

namespace Minds\Core\Features\Services;

use Minds\Core\Di\Di;
use Minds\Common\Jwt;
use Minds\Core\Config\Config;
use Minds\Core\Log\Logger;

/**
 * Setting feature flags and experiments via signed jwt cookies via cypress
 * @package Minds\Core\Features\Services
 */
class Cypress extends BaseService
{
    public function __construct(
        private ?Jwt $jwt = null,
        private ?Logger $logger = null,
        private ?Config $config = null
    ) {
        $this->config ??= Di::_()->get('Config');
        $this->logger ??= Di::_()->get('Logger');
        $this->jwt ??= new Jwt();
        $this->jwt->setKey($this->config->get('cypress')['shared_key']);
    }

    /**
     * @inheritDoc
     */
    public function getReadableName(): string
    {
        return 'Cookie';
    }

    /**
     * @inheritDoc
     */
    public function sync(int $ttl): bool
    {
        // No need for sync
        return true;
    }

    /**
     * @inheritDoc
     */
    public function fetch(array $keys): array
    {
        $output = [];

        $jwtToken = $_COOKIE['force_experiment_variations'] ?? null;
        
        if (!$jwtToken) {
            return $output;
        }

        try {
            $jwtTokenDecoded = $this->jwt->decode($jwtToken);
        } catch (\Exception $e) {
            $this->logger->error($e);
            return [];
        }

        $keys = $jwtTokenDecoded['data'];
        
        foreach ($keys as $key => $value) {
            $output[$key] = (bool) $value;
        }

        return $output;
    }
}
