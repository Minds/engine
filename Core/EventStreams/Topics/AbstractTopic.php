<?php
/**
 * Abstract topic, provides access to the Pulsar client
 */
namespace Minds\Core\EventStreams\Topics;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver;
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
     * @return Client
     */
    protected function client(): Client
    {
        $pulsarConfig = $this->config->get('pulsar');
        $pulsarHost = $pulsarConfig['host'] ?? 'pulsar';
        $pulsarPort = $pulsarConfig['port'] ?? 6650;
        $pulsarSchema = ($pulsarConfig['ssl'] ?? true) ? 'pulsar+ssl' : 'pulsar';

        $clientConfig = new ClientConfiguration();

        if ($pulsarConfig['ssl'] ?? true) {
            $clientConfig->setUseTls(true)
                ->setTlsAllowInsecureConnection($pulsarConfig['ssl_skip_verify'] ?? false)
                ->setTlsTrustCertsFilePath($pulsarConfig['ssl_cert_path'] ?? '/var/secure/pulsar.crt');
        }

        if ($this->client) {
            return $this->client;
        }

        $this->client = new Client();
        $this->client->init("$pulsarSchema://$pulsarHost:$pulsarPort", $clientConfig);

        return $this->client;
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
