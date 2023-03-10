<?php
declare(strict_types=1);

namespace Minds\Common\Pulsar;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Pulsar\Compression\Compression;
use Pulsar\Consumer;
use Pulsar\ConsumerOptions;
use Pulsar\Exception\IOException;
use Pulsar\Exception\OptionsException;
use Pulsar\Exception\RuntimeException;
use Pulsar\Producer;
use Pulsar\ProducerOptions;

/**
 * Minds Pulsar Client wrapper
 */
class Client
{
    private ?Producer $producer = null;
    private ?Consumer $consumer = null;

    public function __construct(
        private ?Config $config = null
    ) {
        $this->config ??= Di::_()->get('Config');
    }

    /**
     * @param string $topic
     * @param ProducerOptions $options
     * @return Producer
     * @throws RuntimeException
     * @throws IOException
     * @throws OptionsException
     */
    public function createProducer(string $topic, ProducerOptions $options): Producer
    {
        if ($this->producer !== null) {
            return $this->producer;
        }

        [
            'host' => $host,
            'port' => $port,
            'schema' => $schema,
        ] = $this->getMindsPulsarConfig();

        $options->setCompression(Compression::ZLIB);
        $options->setTopic($topic);

        $producer = new Producer("$schema://$host:$port", $options);
        $producer->connect();

        return $this->producer = $producer;
    }

    /**
     * @param string $topic
     * @param string $subscriptionId
     * @param ConsumerOptions $options
     * @return Consumer
     * @throws IOException
     * @throws OptionsException
     */
    public function subscribeWithRegex(string $topic, string $subscriptionId, ConsumerOptions $options): Consumer
    {
        if ($this->consumer !== null) {
            return $this->consumer;
        }

        [
            'host' => $host,
            'port' => $port,
            'schema' => $schema,
        ] = $this->getMindsPulsarConfig();

        $options->setSubscription($subscriptionId);
        $options->setTopic($topic);

        $consumer = new Consumer("$schema://$host:$port", $options);
        $consumer->connect();

        return $this->consumer = $consumer;
    }

    private function getMindsPulsarConfig(): array
    {
        $pulsarConfig = $this->config->get('pulsar');
        return [
            'host' => $pulsarConfig['host'] ?? 'pulsar',
            'port' => $pulsarConfig['port'] ?? 6650,
            'schema' => ($pulsarConfig['ssl'] ?? true) ? 'pulsar+ssl' : 'pulsar'

        ];
    }

    /**
     * @return void
     * @throws IOException
     */
    public function close(): void
    {
        $this->producer?->close();
        $this->consumer?->close();
    }
}
