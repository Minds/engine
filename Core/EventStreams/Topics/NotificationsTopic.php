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

use Exception;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\NotificationEvent;
use Minds\Core\Notifications;
use Pulsar\Consumer;
use Pulsar\ConsumerOptions;
use Pulsar\Exception\IOException;
use Pulsar\Exception\MessageNotFound;
use Pulsar\Exception\OptionsException;
use Pulsar\Exception\RuntimeException;
use Pulsar\MessageOptions;
use Pulsar\Producer;
use Pulsar\ProducerOptions;
use Pulsar\Schema\SchemaJson;
use Pulsar\SubscriptionType;

class NotificationsTopic extends AbstractTopic implements TopicInterface
{
    /** @var int */
    const DELAY_SECONDS = 30; // 30 second delay

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
     * @return bool
     */
    public function send(EventInterface $event): bool
    {
        if (!$event instanceof NotificationEvent) {
            return false;
        }

        // Build the message
        $message = (object) [
            'uuid' => $event->getNotification()->getUuid(),
            'urn' => $event->getNotification()->getUrn(),
            'to_guid' => $event->getNotification()->getToGuid(),
            'from_guid' => $event->getNotification()->getFromGuid(),
            'entity_urn' => $event->getNotification()->getEntityUrn(),
        ];

        // Send the event to the stream
        try {
            $this->getProducer()->send(
                payload: $message,
                options: [
                    MessageOptions::DELAY_SECONDS => static::DELAY_SECONDS,
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
     * Consume stream events. Use a new $subscriptionId per service
     * eg. push, emails
     * @param string $subscriptionId
     * @param callable $callback - the logic for the event
     * @param string $topicRegex - defaults to * (all topics will be returned)
     * @return void
     * @throws IOException
     * @throws OptionsException
     * @throws RuntimeException
     * @throws MessageNotFound
     * @throws Exception
     */
    public function consume(string $subscriptionId, callable $callback, string $topicRegex = '*'): void
    {
        $consumer = $this->getConsumer($subscriptionId);

        while (true) {
            $message = $consumer->receive();
            try {
                $data = json_decode($message->getPayload(), true);

                $notification = $this->notificationsManager->getByUrn($data['urn']);

                if (!$notification) {
                    // Not found, it may already have been merged
                    $consumer->ack($message);
                    continue;
                }

                $event = new NotificationEvent();
                $event->setNotification($notification)
                    ->setTimestamp($message->getProperties()['event_timestamp']);

                if (call_user_func($callback, $event, $message) === true) {
                    $consumer->ack($message);
                }
            } catch (Exception $e) {
                $consumer->nack($message);
            }
        }
    }

    /**
     * @return Producer
     * @throws IOException
     * @throws OptionsException
     * @throws RuntimeException
     */
    private function getProducer(): Producer
    {
        $tenant = $this->getPulsarTenant();
        $namespace = $this->getPulsarNamespace();
        $topic = 'event-notification';

        // Build the config and include the schema
        $config = new ProducerOptions();
        $config->setSchema(
            new SchemaJson(
                $this->getSchema(),
                [
                    'key' => 'value'
                ]
            )
        );

        return $this->client()->createProducer("persistent://$tenant/$namespace/$topic", $config);
    }

    /**
     * @param string $subscriptionId
     * @return Consumer
     * @throws IOException
     * @throws OptionsException
     */
    private function getConsumer(string $subscriptionId): Consumer
    {
        $tenant = $this->getPulsarTenant();
        $namespace = $this->getPulsarNamespace();
        $topic = 'event-notification';

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

        return $this->client()->subscribeWithRegex("persistent://$tenant/$namespace/$topic", $subscriptionId, $config);
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
