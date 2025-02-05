<?php
declare(strict_types=1);

namespace Minds\Core\EventStreams\Topics;

use Exception;
use Minds\Core\Chat\Notifications\Events\ChatNotificationEvent;
use Minds\Core\EventStreams\EventInterface;
use Pulsar\Consumer;
use Pulsar\ConsumerConfiguration;
use Pulsar\MessageBuilder;
use Pulsar\Producer;
use Pulsar\ProducerConfiguration;
use Pulsar\Result;
use Pulsar\SchemaType;

class ChatNotificationsTopic extends AbstractTopic implements TopicInterface
{
    private const DELAY_MS = 0; // No delay, the consumer should reject
    private const TOPIC = "chat-event-notifications";

    private ?Producer $producer = null;

    /**
     * @inheritDoc
     */
    public function send(EventInterface $event): bool
    {
        if (!$event instanceof ChatNotificationEvent) {
            return false;
        }

        $data = [
            'entity_urn' => $event->entityUrn,
            'from_guid' => (string) $event->fromGuid,
            'tenant_id' => $this->config->get('tenant_id')
        ];

        $message = (new MessageBuilder())
            ->setDeliverAfter(self::DELAY_MS)
            ->setEventTimestamp($event->getTimestamp() ?: time())
            ->setContent(json_encode($data))
            ->build();

        return $this->getProducer()->send($message) === Result::ResultOk;
    }

    /**
     * @inheritDoc
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

        while(true) {
            $message = $consumer->receive();
            try {
                $data = json_decode($message->getDataAsString(), true);

                $event = new ChatNotificationEvent(
                    entityUrn: $data['entity_urn'],
                    fromGuid: (int) $data['from_guid']
                );
                $event->setTimestamp($message->getEventTimestamp());

                if (isset($data['tenant_id']) && $tenantId = $data['tenant_id']) {
                    $this->getMultiTenantBootService()->bootFromTenantId($tenantId);
                }

                if (call_user_func($callback, $event, $message)) {
                    $consumer->acknowledge($message);
                    continue;
                }
                $consumer->negativeAcknowledge($message);
            } catch (Exception $e) {
                $consumer->negativeAcknowledge($message);
            } finally {
                // Reset Multi Tenant support
                if ($tenantId ?? null) {
                    $this->getMultiTenantBootService()->resetRootConfigs();
                }
            }
        }
    }

    private function getProducer(): Producer
    {
        if ($this->producer) {
            return $this->producer;
        }

        $config = new ProducerConfiguration();
        $config->setSchema(SchemaType::AVRO, "chat_notification", $this->getSchema(), []);

        return $this->producer = $this->client()
            ->createProducer(
                "persistent://{$this->getPulsarTenant()}/{$this->getPulsarNamespace()}/" . self::TOPIC,
                $config
            );
    }

    private function getConsumer(string $subscriptionId): Consumer
    {
        return $this->client()
            ->subscribe(
                "persistent://{$this->getPulsarTenant()}/{$this->getPulsarNamespace()}/" . self::TOPIC,
                $subscriptionId,
                (new ConsumerConfiguration())
                    ->setConsumerType(Consumer::ConsumerShared)
                    ->setUnAckedMessagesTimeoutMs(10000) // Redeliver after 10 seconds
                    ->setSchema(SchemaType::AVRO, "chat_notification", $this->getSchema(), [])
            );
    }

    private function getSchema(): string
    {
        return json_encode([
            'type' => 'record',
            'name' => 'chat_notification',
            'namespace' => 'engine',
            'fields' => [
                ['name' => 'entity_urn', 'type' => 'string'],
                ['name' => 'tenant_id', 'type' => ['null', 'int']],
                ['name' => 'from_guid', 'type' => 'string'],
            ]
        ]);
    }
}
