<?php
declare(strict_types=1);

namespace Minds\Core\EventStreams\Topics;

use Exception;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Events\ViewEvent;
use Minds\Helpers\MagicAttributes;
use Pulsar\Consumer;
use Pulsar\ConsumerOptions;
use Pulsar\Exception\IOException;
use Pulsar\Exception\OptionsException;
use Pulsar\Exception\RuntimeException;
use Pulsar\MessageOptions;
use Pulsar\Producer;
use Pulsar\ProducerOptions;
use Pulsar\Schema\SchemaJson;
use Pulsar\SubscriptionType;

class ViewsTopic extends AbstractTopic implements TopicInterface
{
    public const TOPIC = "event-view";

    /**
     * @param EventInterface $event
     * @return bool
     */
    public function send(EventInterface $event): bool
    {
        if (!($event instanceof ViewEvent)) {
            return false;
        }
        try {
            $this->getProducer()->send(
                payload: $this->createMessage($event),
                options: [
                    MessageOptions::PROPERTIES => [
                        'event_timestamp' => $event->getTimestamp() ?? time()
                    ]
                ]
            );

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Generates a producer
     * @return Producer
     * @throws IOException
     * @throws OptionsException
     * @throws RuntimeException
     */
    private function getProducer(): Producer
    {
        $config = new ProducerOptions();
        $config
            ->setSchema(
                new SchemaJson(
                    $this->getSchema(),
                    [
                        'key' => 'value'
                    ]
                )
            );

        return $this->client()->createProducer(
            "persistent://{$this->getPulsarTenant()}/{$this->getPulsarNamespace()}/" . self::TOPIC,
            $config
        );
    }

    /**
     * Generates a Pulsar message for a view event
     * @param ViewEvent $event
     * @return object
     */
    private function createMessage(ViewEvent $event): object
    {
        return (object) [
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
        ];
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
     * @throws Exception
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
     * @throws IOException
     * @throws OptionsException
     */
    private function getConsumer(string $subscriptionId): Consumer
    {
        $config = new ConsumerOptions();
        $config->setSchema(
            new SchemaJson(
                $this->getSchema(),
                [
                    'key' => 'value'
                ]
            )
        );
        $config->setSubscriptionType(SubscriptionType::Shared);


        return $this->client()->subscribeWithRegex(
            topic: "persistent://{$this->getPulsarTenant()}/{$this->getPulsarNamespace()}/" . self::TOPIC,
            subscriptionId: $subscriptionId,
            options: $config
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
            ]
        ]);
    }
}
