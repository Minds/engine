<?php
declare(strict_types=1);

namespace Minds\Core\EventStreams\Topics;

use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Events\ViewEvent;
use Minds\Helpers\MagicAttributes;
use Pulsar\Consumer;
use Pulsar\ConsumerConfiguration;
use Pulsar\Message;
use Pulsar\MessageBuilder;
use Pulsar\Producer;
use Pulsar\ProducerConfiguration;
use Pulsar\Result;
use Pulsar\SchemaType;

class ViewsTopic extends AbstractTopic implements TopicInterface
{
    public const TOPIC = "event-view";

    /**
     * @inheritDoc
     */
    public function send(EventInterface $event): bool
    {
        if (!($event instanceof ViewEvent)) {
            return false;
        }

        $producer = $this->getProducer();

        return $producer->send($this->createMessage($event)) === Result::ResultOk;
    }

    /**
     * Generates a producer
     * @return Producer
     */
    private function getProducer(): Producer
    {
        return $this->client()->createProducer(
            "persistent://{$this->getPulsarTenant()}/{$this->getPulsarNamespace()}/" . self::TOPIC,
            (new ProducerConfiguration())
                ->setSchema(SchemaType::AVRO, "view", $this->getSchema())
        );
    }

    /**
     * Generates a Pulsar message for a view event
     * @param ViewEvent $event
     * @return Message
     */
    private function createMessage(ViewEvent $event): Message
    {
        $userGuid = $event->getUser() ? (string) $event->getUser()->getGuid() : null;
        return (new MessageBuilder())
            ->setEventTimestamp($event->getTimestamp() ?: time())
            ->setContent(
                json_encode([
                    'user_guid' => $userGuid,
                    'entity_urn' => (string) $event->getEntity()->getUrn(),
                    'entity_guid' => (string) $event->getEntity()->getUrn(),
                    'entity_owner_guid' => (string) $event->getEntity()->getOwnerGuid(),
                    'entity_type' => MagicAttributes::getterExists($event->getEntity(), 'getType') ? (string) $event->getEntity()->getType() : '',
                    'entity_subtype' => MagicAttributes::getterExists($event->getEntity(), 'getSubtype') ? (string) $event->getEntity()->getSubtype() : '',
                    'cm_platform' => $event->cmPlatform,
                    'cm_source' => $event->cmSource,
                    'cm_salt' => $event->cmSalt,
                    'cm_medium' => $event->cmMedium,
                    'cm_campaign' => $event->cmCampaign,
                    'cm_page_token' => $event->cmPageToken,
                    'cm_delta' => $event->cmDelta,
                    'cm_position' => $event->cmPosition,
                    'cm_served_by_guid' => $event->cmServedByGuid,
                    'view_uuid' => $event->viewUUID,
                    'external' => $event->external,
                    'tenant_id' => $this->config->get('tenant_id') ?: -1,
                ])
            )
            ->build();
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

        $this->processBatch(
            consumer: $consumer,
            callback: $callback,
            batchTotalAmount: $batchTotalAmount,
            execTimeoutInSeconds: $execTimeoutInSeconds,
            onBatchConsumed: $onBatchConsumed
        );
    }


    /**
     * Generates a Pulsar consumer
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
                ->setSchema(SchemaType::AVRO, "view", $this->getSchema(), [])
        );
    }

    /**
     * Defines the topic's AVRO schema
     * @return string
     */
    private function getSchema(): string
    {
        return json_encode([
            'type' => 'record',
            'name' => 'view',
            'namespace' => $this->getPulsarNamespace(),
            'fields' => [
                [
                    'name' => 'user_guid',
                    'type' => 'string',
                ],
                [
                    'name' => 'entity_urn',
                    'type' => 'string',
                ],
                [
                    'name' => 'entity_guid',
                    'type' => 'string',
                ],
                [
                    'name' => 'entity_owner_guid',
                    'type' => 'string',
                ],
                [
                    'name' => 'entity_type',
                    'type' => 'string'
                ],
                [
                    'name' => 'entity_subtype',
                    'type' => 'string'
                ],
                [
                    'name' => 'cm_platform',
                    'type' => 'string'
                ],
                [
                    'name' => 'cm_source',
                    'type' => 'string'
                ],
                [
                    'name' => 'cm_salt',
                    'type' => 'string'
                ],
                [
                    'name' => 'cm_medium',
                    'type' => 'string'
                ],
                [
                    'name' => 'cm_campaign',
                    'type' => 'string'
                ],
                [
                    'name' => 'cm_page_token',
                    'type' => 'string'
                ],
                [
                    'name' => 'cm_delta',
                    'type' => 'string'
                ],
                [
                    'name' => 'cm_position',
                    'type' => 'string'
                ],
                [
                    'name' => 'cm_served_by_guid',
                    'type' => 'string'
                ],
                [
                    'name' => 'view_uuid',
                    'type' => 'string'
                ],
                [
                    'name' => 'external',
                    'type' => 'boolean'
                ],
                [
                    'name' => 'tenant_id',
                    'type' => 'int'
                ],
            ]
        ]);
    }
}
