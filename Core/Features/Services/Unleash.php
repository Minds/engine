<?php
/**
 * Unleash
 *
 * @author edgebal
 */

namespace Minds\Core\Features\Services;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\UnleashClient\Config as UnleashConfig;
use Minds\UnleashClient\Entities\Context;
use Minds\UnleashClient\Unleash as UnleashClient;

/**
 * Unleash server (GitLab FF) feature flags service
 * @package Minds\Core\Features\Services
 */
class Unleash extends BaseService
{
    /** @var Config */
    protected $config;

    /** @var UnleashClient */
    protected $unleash;

    /**
     * Unleash constructor.
     * @param Config $config
     * @param UnleashClient $unleash
     */
    public function __construct(
        $config = null,
        $unleash = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->unleash = $unleash ?: $this->initUnleashClient();
    }

    /**
     * Initializes Unleash client package
     * @return UnleashClient
     */
    public function initUnleashClient(): UnleashClient
    {
        $configValues = $this->config->get('unleash');

        $config = new UnleashConfig(
            getenv('UNLEASH_API_URL') ?: ($configValues['apiUrl'] ?? null),
            getenv('UNLEASH_INSTANCE_ID') ?: ($configValues['instanceId'] ?? null),
            getenv('MINDS_ENV') ?: ($configValues['applicationName'] ?? 'development'),
            $configValues['pollingIntervalSeconds'] ?? null,
            $configValues['metricsIntervalSeconds'] ?? null
        );

        $logger = Di::_()->get('Logger\Singleton');
        $cache = Di::_()->get('Cache\PsrWrapper');

        return new UnleashClient($config, $logger, null, $cache);
    }

    /**
     * @inheritDoc
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function fetch(array $keys): array
    {
        $context = new Context();
        $context
            ->setUserGroups($this->getUserGroups())
            ->setRemoteAddress($_SERVER['REMOTE_ADDR'] ?? '')
            ->setHostName($_SERVER['HTTP_HOST'] ?? '');

        if ($this->user) {
            $context
                ->setUserId((string) $this->user->guid);
        }

        // Return whitelisted 'features' array with its values resolved

        return array_intersect_key(
            $this->unleash
                ->setContext($context)
                ->export(),
            array_flip($keys)
        );
    }
}
