<?php
/**
 * This is not the topic to create notifications.
 * Use the Core\Notifications\Manager->add() function to create manual notifications or
 * create an ActionEvent.
 *
 * This is the topic where created notifications are pushed to
 * Push notifications and emails, for example, will consume these notifications.
 */
namespace Minds\Core\EventStreams\Topics;

use Minds\Core\Di\Di;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\NotificationEvent;
use Minds\Core\Notifications;
use PDOException;
use Pulsar\Consumer;
use Pulsar\ConsumerConfiguration;
use Pulsar\MessageBuilder;
use Pulsar\Producer;
use Pulsar\ProducerConfiguration;
use Pulsar\Result;
use Pulsar\SchemaType;

class NotificationsTopic extends AbstractTopic implements TopicInterface
{
    /** @var int */
    const DELAY_MS = 30000; // 30 second delay

    /** @var Notifications\Manager */
    protected $notificationsManager;

    /** @var Producer */
    protected $producer;

    public function __construct(
        Notifications\Manager $notificationsManager = null,
        ...$deps
    ) {
        parent::__construct(...$deps);
        $this->notificationsManager = $notificationsManager ?? Di::_()->get('Notifications\Manager');
    }

    /**
     * Sends notifications events to our stream
     * @param EventInterface $event
     */
    public function send(EventInterface $event): bool
    {
        if (!$event instanceof NotificationEvent) {
            return false;
        }

        // Build the message

        $data = [
            'uuid' => $event->getNotification()->getUuid(),
            'urn' => $event->getNotification()->getUrn(),
            'to_guid' => $event->getNotification()->getToGuid(),
            'from_guid' => $event->getNotification()->getFromGuid(),
            'entity_urn' => $event->getNotification()->getEntityUrn(),
        ];

        if ($tenantId = $this->config->get('tenant_id')) {
            $data['tenant_id'] = $tenantId;
        }

        $builder = new MessageBuilder();
        $message = $builder
            ->setDeliverAfter(static::DELAY_MS) // Wait 30 seconds before consumers will see this
            //->setPartitionKey(0)
            ->setEventTimestamp($event->getTimestamp() ?: time())
            ->setContent(json_encode($data))
            ->build();

        // Send the event to the stream

        $result = $this->getProducer()->send($message);

        if ($result != Result::ResultOk) {
            return false;
        }

        return true;
    }

    /**
     * Consume stream events. Use a new $subscriptionId per service
     * e.g. push, emails
     * @param string $subscriptionId
     * @param callable $callback - the logic for the event
     * @param string $topicRegex - defaults to * (all topics will be returned)
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
        $tenant = $this->getPulsarTenant();
        $namespace = $this->getPulsarNamespace();
        $topic = 'event-notification';

        $config = new ConsumerConfiguration();
        $config->setConsumerType(Consumer::ConsumerShared);
        $config->setSchema(SchemaType::AVRO, "notification", $this->getSchema(), []);

        $consumer = $this->client()->subscribe("persistent://$tenant/$namespace/$topic", $subscriptionId, $config);

        while (true) {
            try {
                $message = $consumer->receive();
                $data = json_decode($message->getDataAsString(), true);

                // Multi tenant support

                if (isset($data['tenant_id']) && $tenantId = $data['tenant_id']) {
                    $this->getMultiTenantBootService()->bootFromTenantId($tenantId);
                }

                $notification = $this->notificationsManager->getByUrn($data['urn']);

                if (!$notification) {
                    $this->logger->warning("Notification not found", [
                        'urn' => $data['urn'],
                        'uuid' => $data['uuid'],
                    ]);
                    // Not found, it may already have been merged
                    $consumer->acknowledge($message);
                    continue;
                }

                $event = new NotificationEvent();
                $event->setNotification($notification)
                    ->setTimestamp($message->getEventTimestamp());

                if (call_user_func($callback, $event, $message) === true) {
                    $consumer->acknowledge($message);
                }
            } catch (\Exception $e) {
                $consumer->negativeAcknowledge($message);
                $this->logger->error("Topic(Consume): Uncaught error: " . $e->getMessage());
                if ($e instanceof PDOException && $e->getCode() === 2006) {
                    throw $e;
                }
            } finally {
                // Reset Multi Tenant support
                if ($tenantId ?? null) {
                    $this->getMultiTenantBootService()->resetRootConfigs();
                }
            }
        }
    }

    /**
     * @return Producer
     */
    protected function getProducer(): Producer
    {
        if ($this->producer) {
            return $this->producer;
        }

        $tenant = $this->getPulsarTenant();
        $namespace = $this->getPulsarNamespace();
        $topic = 'event-notification';

        // Build the config and include the schema

        $config = new ProducerConfiguration();
        $config->setSchema(SchemaType::AVRO, "notification", $this->getSchema(), []);

        return $this->producer = $this->client()->createProducer("persistent://$tenant/$namespace/$topic", $config);
    }

    /**
     * Return the schema
     * @return string
     */
    protected function getSchema(): string
    {
        return json_encode([
            'type' => 'record', // ??
            'name' => 'notification',
            'namespace' => 'engine',
            'fields' => [
                [
                    'name' => 'uuid',
                    'type' => 'string',
                ],
                [
                    'name' => 'urn',
                    'type' => 'string',
                ],
                [
                    'name' => 'to_guid',
                    'type' => 'string',
                ],
                [
                    'name' => 'entity_urn',
                    'type' => 'string',
                ],
                [
                    'name' => 'from_guid',
                    'type' => 'string',
                ],
                [
                    'name' => 'tenant_id',
                    'type' => 'int'
                ],
            ]
        ]);
    }
}
