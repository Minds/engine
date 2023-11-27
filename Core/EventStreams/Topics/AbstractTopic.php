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
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Pulsar\Client as PulsarClient;
use Pulsar\Consumer;
use Pulsar\Exception\IOException;
use Pulsar\Message;

abstract class AbstractTopic
{
    private static array $batchMessages = [];
    private static array $processedMessages = [];
    private static int $startTime = 0;

    /** @var Config */
    protected Config $config;

    /** @var EntitiesBuilder */
    protected EntitiesBuilder $entitiesBuilder;

    /** @var Resolver */
    protected $entitiesResolver;

    public function __construct(
        private ?PulsarClient      $client = null,
        Config            $config = null,
        EntitiesBuilder   $entitiesBuilder = null,
        Resolver          $entitiesResolver = null,
        protected ?Logger $logger = null,
        protected ?MultiTenantBootService $multiTenantBootService = null
    ) {
        $this->config = $config ?? Di::_()->get('Config');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->entitiesResolver = $entitiesResolver ?? new Resolver();

        $this->logger ??= Di::_()->get('Logger');

        $this->multiTenantBootService = $multiTenantBootService;
    }

    /**
     * Return the pulsar client
     * @return PulsarClient
     */
    protected function client(): PulsarClient
    {
        return $this->client ??= Di::_()->get(PulsarClient::class);
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
        return (json_decode($message->getDataAsString()))->view_uuid;
    }

    /**
     * Processing a batch of messages
     * @param Consumer $consumer
     * @param callable $callback
     * @param int $batchTotalAmount
     * @param int $execTimeoutInSeconds
     * @param callable $onBatchConsumed
     * @return void
     * @throws Exception
     */
    protected function processBatch(
        Consumer $consumer,
        callable $callback,
        int $batchTotalAmount,
        int $execTimeoutInSeconds,
        callable $onBatchConsumed
    ): void {
        $this->logger->info("ViewsTopic - processBatch");
        while (true) {
            try {
                $message = $consumer->receive();
                $this->logger->info("Message", [$message->getMessageId()]);
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
                $this->logger->info("Last start time loop: " . self::$startTime);

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
     * @throws Exception
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

    protected function getMultiTenantBootService(): MultiTenantBootService
    {
        return $this->multiTenantBootService ??= Di::_()->get(MultiTenantBootService::class);
    }
}
