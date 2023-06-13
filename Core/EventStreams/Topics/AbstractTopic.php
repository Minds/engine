<?php
/**
 * Abstract topic, provides access to the Pulsar client
 */
namespace Minds\Core\EventStreams\Topics;

use Exception;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Resolver;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Pulsar\Client;
use Pulsar\ClientConfiguration;
use Pulsar\Consumer;
use Pulsar\Message;

abstract class AbstractTopic
{
    private static array $batchMessages = [];
    private static array $processedMessages = [];

    private static int $startTime = 0;
    
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
        Resolver $entitiesResolver = null,
        protected ?Logger $logger = null
    ) {
        $this->client = $client ?? null;
        $this->config = $config ?? Di::_()->get('Config');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->entitiesResolver = $entitiesResolver ?? new Resolver();

        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * Return the pulsar client
     * @return Client
     */
    protected function client(): Client
    {
        if ($this->client) {
            return $this->client;
        }
        
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
     * @param Message $message
     * @return string
     */
    protected function getBatchMessageId(Message $message): string
    {
        return json_decode($message->getDataAsString())->view_uuid;
    }

    /**
     * Processing a batch of messages
     * @param Consumer $consumer
     * @param callable $callback
     * @param int $batchTotalAmount
     * @param int $execTimeoutInSeconds
     * @param callable $onBatchConsumed
     * @return void
     */
    protected function processBatch(
        Consumer $consumer,
        callable $callback,
        int $batchTotalAmount,
        int $execTimeoutInSeconds,
        callable $onBatchConsumed
    ): void {
        while (true) {
            try {
                $message = $consumer->receive();
                if (isset(self::$batchMessages[$this->getBatchMessageId($message)])) {
                    continue;
                }

                self::$batchMessages[$this->getBatchMessageId($message)] = $message;

                /**
                 * Check if either the total messages in the batch have reached the requested size
                 * or
                 * the time expired since the last iteration is greater than the provided timeout
                 *
                 * If either of those 2 conditions is true then process the messages in the batch
                 * otherwise continue receiving messages until one of the 2 conditions above is met
                 */
                if (
                    count(self::$batchMessages) < $batchTotalAmount &&
                    self::$startTime !== 0 &&
                    time() - self::$startTime < $execTimeoutInSeconds
                ) {
                    continue;
                }
                $this->logger->addInfo("Last start time loop: " . self::$startTime);

                self::$startTime = time();
                if (call_user_func($callback, self::$batchMessages) === true) {
                    $this->acknowledgeProcessedMessages($consumer);

                    if ($onBatchConsumed && $this->getTotalMessagesProcessedInBatch() > 0) {
                        call_user_func($onBatchConsumed);
                    }
                }
            } catch (Exception $e) {
                $this->acknowledgeProcessedMessages($consumer);
                if ($onBatchConsumed && $this->getTotalMessagesProcessedInBatch() > 0) {
                    call_user_func($onBatchConsumed);
                }
            }
        }
    }

    /**
     * Acknowledge successfully processed messages in a batch
     * @param Consumer $consumer
     * @return void
     */
    private function acknowledgeProcessedMessages(Consumer $consumer): void
    {
        foreach (self::$processedMessages as $message) {
            $consumer->acknowledge($message);
            unset(self::$batchMessages[$this->getBatchMessageId($message)]);
        }
    }

    /**
     * Adds a message to the list of processed messages
     * @param Message $message
     * @return void
     */
    public function markMessageAsProcessed(Message $message): void
    {
        self::$processedMessages[] = $message;
    }

    /**
     * Gets the current total amount of messages successfully processed
     * @return int
     */
    public function getTotalMessagesProcessedInBatch(): int
    {
        return count(self::$processedMessages);
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
