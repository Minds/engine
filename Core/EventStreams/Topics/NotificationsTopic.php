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
use Pulsar\MessageBuilder;
use Pulsar\ProducerConfiguration;
use Pulsar\ConsumerConfiguration;
use Pulsar\Consumer;
use Pulsar\SchemaType;
use Pulsar\Result;

class NotificationsTopic extends AbstractTopic implements TopicInterface
{
    /** @var int */
    const DELAY_MS = 30000; // 30 second delay

    /** @var Notifications\Manager */
    protected $notificationsManager;
    
    public function __construct(Notifications\Manager $notificationsManager = null, ...$deps)
    {
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

        $tenant = $this->getPulsarTenant();
        $namespace = $this->getPulsarNamespace();
        $topic = 'event-notification';

        // Build the config and include the schema

        $config = new ProducerConfiguration();
        $config->setSchema(SchemaType::AVRO, "notification", $this->getSchema(), []);

        $producer = $this->client()->createProducer("persistent://$tenant/$namespace/$topic", $config);

        // Build the message

        $builder = new MessageBuilder();
        $message = $builder
            ->setDeliverAfter(static::DELAY_MS) // Wait 30 seconds before consumers will see this
            //->setPartitionKey(0)
            ->setContent(json_encode([
                'uuid' => $event->getNotification()->getUuid(),
                'urn' => $event->getNotification()->getUrn(),
                'to_guid' => $event->getNotification()->getToGuid(),
                'from_guid' => $event->getNotification()->getFromGuid(),
                'entity_urn' => $event->getNotification()->getEntityUrn(),
            ]))
            ->build();

        // Send the event to the stream

        $result = $producer->send($message);

        if ($result != Result::ResultOk) {
            return false;
        }

        return true;
    }

    /**
     * Consume stream events. Use a new $subscriptionId per service
     * eg. push, emails
     * @param string $subscriptionId
     * @param callable $callback - the logic for the event
     * @param string $topicRegex - defaults to * (all topics will be returned)
     * @return void
     */
    public function consume(string $subscriptionId, callable $callback, string $topicRegex = '*'): void
    {
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

                $notification = $this->notificationsManager->getByUrn($data['urn']);

                if (!$notification) {
                    // Not found, it may already have been merged
                    $consumer->acknowledge($message);
                    continue;
                }

                $event = new NotificationEvent();
                $event->setNotification($notification);

                if (call_user_func($callback, $event, $message) === true) {
                    $consumer->acknowledge($message);
                }
            } catch (\Exception $e) {
            }
        }
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
            ]
        ]);
    }
}
