<?php
/**
 * Abstract topic, provides access to the Pulsar client
 */
namespace Minds\Core\EventStreams\Topics;

use Minds\Common\Pulsar\Client as PulsarClient;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver;
use Minds\Core\EntitiesBuilder;
use Pulsar\Client;
use Pulsar\Exception\IOException;

abstract class AbstractTopic
{
    /** @var PulsarClient */
    protected $client;

    /** @var Config */
    protected $config;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Resolver */
    protected $entitiesResolver;

    public function __construct(
        Client $client = null,
        Config $config = null,
        EntitiesBuilder $entitiesBuilder = null,
        Resolver $entitiesResolver = null
    ) {
        $this->client = $client ?? null;
        $this->config = $config ?? Di::_()->get('Config');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->entitiesResolver = $entitiesResolver ?? new Resolver();
    }

    /**
     * Return the pulsar client
     * @return PulsarClient
     */
    protected function client(): PulsarClient
    {
        return $this->client = new PulsarClient();
    }

    /**
     * Get pulsar tenant.
     * A tenant is name of platform
     * @return string
     */
    protected function getPulsarTenant(): string
    {
        return $this->config->get('pulsar')['tenant'] ?? 'minds-com';
    }

    /**
     * The namespace is the product/service name
     * - eg. engine, backend, frontend
     * @return string
     */
    protected function getPulsarNamespace(): string
    {
        return $this->config->get('pulsar')['namespace'] ?? 'engine';
    }

    /**
     * Close the connection
     * @throws IOException
     */
    public function __destruct()
    {
        $this->client?->close();
    }
}
