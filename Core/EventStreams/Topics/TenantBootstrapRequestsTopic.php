<?php
declare(strict_types=1);

namespace Minds\Core\EventStreams\Topics;

use Exception;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Events\TenantBootstrapRequestEvent;
use Pulsar\Consumer;
use Pulsar\ConsumerConfiguration;
use Pulsar\MessageBuilder;
use Pulsar\Producer;
use Pulsar\ProducerConfiguration;
use Pulsar\SchemaType;

/**
 * Pulsar topic for tenant bootstrap requests.
 */
class TenantBootstrapRequestsTopic extends AbstractTopic implements TopicInterface
{
    /** Topic name. */
    public const TOPIC = "tenant-bootstrap-requests";

    /**
     * @param EventInterface $event
     * @return bool
     */
    public function send(EventInterface $event): bool
    {
        if (!$event instanceof TenantBootstrapRequestEvent) {
            return false;
        }

        $producer = $this->getProducer();

        $result = $producer->send(
            (new MessageBuilder())
                ->setEventTimestamp($event->getTimestamp() ?: time())
                ->setContent(json_encode([
                    'tenant_id' => $event->getTenantId(),
                    'site_url' => $event->getSiteUrl()
                ]))
        );

        return !$result;
    }

    private function getProducer(): Producer
    {
        return $this->client()->createProducer(
            "persistent://{$this->getPulsarTenant()}/{$this->getPulsarNamespace()}/" . self::TOPIC,
            (new ProducerConfiguration())
                ->setSchema(SchemaType::JSON, self::TOPIC, $this->getSchema())
        );
    }

    /**
     * @param string $subscriptionId
     * @param callable $callback
     * @param string $topicRegex
     * @param bool $isBatch
     * @param int $batchTotalAmount
     * @param int $execTimeoutInSeconds
     * @param callable|null $onBatchConsumed
     * @return void
     */
    public function consume(
        string $subscriptionId,
        callable $callback,
        string $topicRegex = '*',
        bool $isBatch = false,
        int $batchTotalAmount = 1,
        int $execTimeoutInSeconds = 30,
        ?callable $onBatchConsumed = null
    ): void {
        $consumer = $this->getConsumer($subscriptionId);

        $this->process($consumer, $callback);
    }

    private function process(Consumer $consumer, callable $callback): void
    {
        while (true) {
            $message = $consumer->receive();
            try {
                $this->logger->info("Received message");
                $data = json_decode($message->getDataAsString());

                // Map data to TenantBootstrapRequestEvent object
                $tenantBootstrapRequest = new TenantBootstrapRequestEvent();

                $tenantBootstrapRequest->setTenantId($data->tenant_id);
                $tenantBootstrapRequest->setSiteUrl($data->site_url);

                $this->logger->info("", [
                    'tenant_id' => $tenantBootstrapRequest->getTenantId(),
                    'site_url' => $tenantBootstrapRequest->getSiteUrl(),
                ]);

                if (call_user_func($callback, $tenantBootstrapRequest) === false) {
                    $this->logger->info("Negative acknowledging message");
                    $consumer->negativeAcknowledge($message);
                    continue;
                }
                $this->logger->info("Acknowledging message");
                $consumer->acknowledge($message);
            } catch (Exception $e) {
                $consumer->negativeAcknowledge($message);
            }
        }
    }

    /**
     * @param string $subscriptionId
     * @return Consumer
     */
    private function getConsumer(string $subscriptionId): Consumer
    {
        return $this->client()->subscribeWithRegex(
            "persistent://{$this->getPulsarTenant()}/{$this->getPulsarNamespace()}/" . self::TOPIC,
            $subscriptionId,
            (new ConsumerConfiguration())
                ->setConsumerType(Consumer::ConsumerShared)
                ->setSchema(SchemaType::JSON, self::TOPIC, $this->getSchema(), [])
        );
    }

    /**
     * @return string
     */
    private function getSchema(): string
    {
        return json_encode([
            'type' => 'record',
            'name' => 'TenantBootstrapRequest',
            'namespace' => $this->getPulsarNamespace(),
            'fields' => [
                [
                    'name' => 'tenant_id',
                    'type' => [ 'null', 'int' ],
                ],
                [
                    'name' => 'site_url',
                    'type' => [ 'null', 'string' ],
                ]
            ]
        ]);
    }
}
