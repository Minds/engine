<?php
/**
 * FeatureFlags via signed cookies via cypress
 *
 * @author mark
 */

namespace Minds\Core\Features\Services;

use Minds\Core\Di\Di;
use Minds\Common\Jwt;

/**
 * Setting feature flags via signed jwt cookies via cypress
 * @package Minds\Core\Features\Services
 */
class Cypress extends BaseService
{
    /* @var Jwt */
    protected $jwt;

    public function __construct($jwt = null, $config = null)
    {
        $config = $config ?? Di::_()->get('Config');
        $this->jwt = $jwt ?? new Jwt();
        $this->jwt->setKey($config->get('cypress')['shared_key']);
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

        $jwtToken = $_COOKIE['feature_flags_override'] ?? null;
        
        if (!$jwtToken) {
            return $output;
        }

        $jwtTokenDecoded = $this->jwt->decode($jwtToken);
        $keys = $jwtTokenDecoded['data'];
        
        foreach ($keys as $key => $value) {
            $output[$key] = (bool) $value;
        }

        return $output;
    }
}
