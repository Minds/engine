<?php
/**
 * ClientFactory
 *
 * @author edgebal
 */

namespace Minds\Core\Features\Services\Unleash;

use Minds\Core\Config as MindsConfig;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\UnleashClient\Http\Client;
use Minds\UnleashClient\Http\Config;

class ClientFactory
{
    /** @var MindsConfig */
    protected $config;

    /** @var Logger */
    protected $logger;

    /**
     * ClientFactory constructor.
     * @param MindsConfig $config
     * @param Logger $logger
     */
    public function __construct(
        $config = null,
        $logger = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->logger = $logger ?: Di::_()->get('Logger\Singleton');
    }

    /**
     * Builds an Unleash Client using environment configuration
     * @param string $environment
     * @return Client
     */
    public function build(?string $environment): Client
    {
        $environment = $environment ?: ($configValues['applicationName'] ?? 'development');

        $this->logger->info(sprintf("Building Unleash Client for %s", $environment));

        $configValues = $this->config->get('unleash');

        $config = new Config(
            getenv('UNLEASH_API_URL') ?: ($configValues['apiUrl'] ?? null),
            getenv('UNLEASH_INSTANCE_ID') ?: ($configValues['instanceId'] ?? null),
            $environment,
            $configValues['pollingIntervalSeconds'] ?? null,
            $configValues['metricsIntervalSeconds'] ?? null
        );

        return new Client($config, $this->logger);
    }
}
