<?php
/**
 * Abstract topic, provides access to the Pulsar client
 */
namespace Minds\Core\EventStreams\Topics;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Pulsar\Client;
use Pulsar\ClientConfiguration;

abstract class AbstractTopic
{
    /** @var Client */
    protected $client;

    /** @var Config */
    protected $config;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function __construct(Client $client = null, Config $config = null, EntitiesBuilder $entitiesBuilder = null)
    {
        $this->client = $client ?? null;
        $this->config = $config ?? Di::_()->get('Config');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * Return the pulsar client
     * @return Client
     */
    protected function client(): Client
    {
        $pulsarConfig = $this->config->get('pulsar');
        $pulsarHost = $pulsarConfig['host'] ?? 'pulsar';
        $pulsarPort = $pulsarConfig['port'] ?? 6650;
        $pulsarSchema = $pulsarConfig['ssl'] ? 'pulsar+ssl' : 'pulsar';

        $clientConfig = new ClientConfiguration();

        if ($pulsarConfig['ssl']) {
            $clientConfig->setUseTls(true)
                ->setTlsTrustCertsFilePath($pulsarConfig['ssl_cert_path']);
        }

        return $this->client ?? new Client("$pulsarSchema://$pulsarHost:$pulsarPort", $clientConfig);
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
     */
    public function __destruct()
    {
        if ($this->client) {
            $this->client->close();
        }
    }
}
