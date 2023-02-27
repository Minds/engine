<?php
declare(strict_types=1);

namespace Minds\Core\EventStreams\Topics;

use Minds\Core\Di\Di;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Events\ViewEvent;
use Minds\Core\Log\Logger;
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

    private function getLogger(): Logger
    {
        return Di::_()->get("Logger");
    }

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

    private function getProducer(): Producer
    {
        return $this->client()->createProducer(
            "persistent://{$this->getPulsarTenant()}/{$this->getPulsarNamespace()}/" . self::TOPIC,
            (new ProducerConfiguration())
                ->setSchema(SchemaType::AVRO, "view", $this->getSchema())
        );
    }

    private function createMessage(ViewEvent $event): Message
    {
        return (new MessageBuilder())
            ->setEventTimestamp($event->getTimestamp() ?: time())
            ->setContent(
                json_encode([
                    'user_guid' => (string) $event->getUser()->getGuid(),
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
                ])
            )
            ->build();
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

        $this->processBatch(
            consumer: $consumer,
            callback: $callback,
            batchTotalAmount: $batchTotalAmount,
            execTimeoutInSeconds: $execTimeoutInSeconds,
            onBatchConsumed: $onBatchConsumed
        );
    }



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
                    'name' => 'cm_timestamp',
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
            ]
        ]);
    }
}
