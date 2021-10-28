<?php

namespace Minds\Core\EventStreams\Topics;

use Minds\Common\Urn;
use Minds\Core\EventStreams\AdminActionEvent;
use Minds\Core\EventStreams\EventInterface;
use Minds\Entities\User;
use Pulsar\MessageBuilder;
use Pulsar\ProducerConfiguration;
use Pulsar\ConsumerConfiguration;
use Pulsar\Consumer;
use Pulsar\Producer;
use Pulsar\SchemaType;
use Pulsar\Result;

/**
 * Topic for admin events.
 */
class AdminEventTopic extends AbstractTopic implements TopicInterface
{
    // prefix for admin event - action will be added on the end so you can subscribe to specific events.
    public const TOPIC_NAME_PREFIX = 'event-admin-action-';

    /** @var Producer */
    protected $producer = null;

    /** @var Consumer */
    protected $consumer = null;

    public function __construct(...$deps)
    {
        parent::__construct(...$deps);
    }

    /**
     * Sends admin events to our stream
     * @param EventInterface $event - the event we are sending.
     * @return bool true if success.
     */
    public function send(EventInterface $event): bool
    {
        // discard any unwanted events.
        if (!$event instanceof AdminActionEvent) {
            return false;
        }

        // Setup some variables and build the config needed to create the producer.
        $tenant = $this->getPulsarTenant();
        $namespace = $this->getPulsarNamespace();
        $topic = self::TOPIC_NAME_PREFIX . $event->getAction();
        $config = new ProducerConfiguration();
        $config->setSchema(SchemaType::AVRO, "admin_action", $this->getSchema(), []);

        $producer = $this->client()->createProducer("persistent://$tenant/$namespace/$topic", $config);

        // Build message.
        $builder = new MessageBuilder();
        $message = $builder
            ->setEventTimestamp($event->getTimestamp() ?: time())
            ->setContent(json_encode([
                'action' => $event->getAction(), // event action.
                'action_data' => $event->getActionData(), // action data for event.
                'actor_guid' => (string) $event->getActor()->getGuid(), // the actor - the admin.
                'subject_urn' => (string) $event->getSubject()->getUrn(), // the subject - the entity or user.
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
     * Consume stream events. Use a new $subscriptionId per service to avoid conflicts.
     * @param string $subscriptionId - id for a subscription
     * @param callable $callback - callback function for the event
     * @param string $topicRegex - defaults to * (all topics will be returned)
     * @return void
     */
    public function consume(string $subscriptionId, callable $callback, string $topicRegex = '*'): void
    {
        // Setup tennant and config to create consumer
        $tenant = $this->getPulsarTenant();
        $namespace = $this->getPulsarNamespace();
        $config = new ConsumerConfiguration();
        $config->setConsumerType(Consumer::ConsumerShared);
        $config->setSchema(SchemaType::AVRO, "admin_action", $this->getSchema(), []);

        $consumer = $this->client()->subscribeWithRegex("persistent://$tenant/$namespace/$topicRegex", $subscriptionId, $config);

        // Infinite loop (unless exception is thrown).
        while (true) {
            try {
                // wait for message.
                $message = $consumer->receive();

                // parse data.
                $data = json_decode($message->getDataAsString(), true);
                $subject = $this->entitiesResolver->single(new Urn($data['subject_urn']));

                // If no user, something went wrong, but still skip.
                if (!$subject) {
                    $consumer->acknowledge($message);
                    continue;
                }
            
                $actor = $this->entitiesBuilder->single($data['user_guid'] ?? null) ?? new User(null);

                if (!$actor instanceof User) {
                    $this->logger->error('Non-user guid was passed to construct User object for admin event');
                    $consumer->acknowledge($message);
                    continue;
                }

                // create event
                $event = new AdminActionEvent();
                $event->setActor($actor)
                    ->setSubject($subject)
                    ->setAction($data['action'])
                    ->setActionData($data['action_data'])
                    ->setTimestamp($message->getEventTimestamp());

                // call callback function and acknowledge or throw exception on response.
                if (call_user_func($callback, $event, $message) === true) {
                    $consumer->acknowledge($message);
                } else {
                    throw new \Exception("Failed to process message");
                }
            } catch (\Exception $e) {
                $consumer->negativeAcknowledge($message);
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
            'type' => 'record',
            'name' => 'admin_action',
            'namespace' => 'engine',
            'fields' => [
                [
                    'name' => 'action',
                    'type' => 'string',
                ],
                [
                    'name' => 'action_data',
                    'type' => 'string',
                ],
                [
                    'name' => 'actor_guid',
                    'type' => 'string',
                ],
                [
                    'name' => 'entity_urn',
                    'type' => 'string',
                ]
            ]
        ]);
    }
}
