<?php
/**
 * All action events are submitted via this producer
 */
namespace Minds\Core\EventStreams\Topics;

use Exception;
use Minds\Common\Urn;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\EventInterface;
use Minds\Entities\Entity;
use Minds\Entities\User;
use Minds\Helpers\MagicAttributes;
use Pulsar\ConsumerOptions;
use Pulsar\Exception\IOException;
use Pulsar\Exception\OptionsException;
use Pulsar\Exception\RuntimeException;
use Pulsar\MessageOptions;
use Pulsar\ProducerOptions;
use Pulsar\Schema\SchemaJson;
use Pulsar\SubscriptionType;

class ActionEventsTopic extends AbstractTopic implements TopicInterface
{
    /**
     * Sends action events to our stream
     * @param EventInterface $event
     * @return bool
     * @throws OptionsException
     * @throws IOException
     * @throws RuntimeException
     */
    public function send(EventInterface $event): bool
    {
        if (!$event instanceof ActionEvent) {
            return false;
        }

        $tenant = $this->getPulsarTenant();
        $namespace = $this->getPulsarNamespace();
        $topic = 'event-action-' . $event->getAction();

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

        $producer = $this->client()->createProducer("persistent://$tenant/$namespace/$topic", $config);

        // Build the message
        $message = (object) [
            'action' => $event->getAction(),
            'action_data' => $event->getActionData(),
            'user_guid' => (string) $event->getUser()->getGuid(),
            'entity_urn' => (string) $event->getEntity()->getUrn(),
            'entity_guid' => (string) $event->getEntity()->getGuid(),
            'entity_owner_guid' => (string) $event->getEntity()->getOwnerGuid(),
            'entity_type' => MagicAttributes::getterExists($event->getEntity(), 'getType') ? (string) $event->getEntity()->getType() : '',
            'entity_subtype' => MagicAttributes::getterExists($event->getEntity(), 'getSubtype') ? (string) $event->getEntity()->getSubtype() : '',
        ];

        // Send the event to the stream
        try {
            $producer->send(
                payload: $message,
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
     * Consume stream events. Use a new $subscriptionId per service
     * eg. notifications, analytics, recomendations
     * @param string $subscriptionId
     * @param callable $callback - the logic for the event
     * @param string $topicRegex - defaults to * (all topics will be returned)
     * @return void
     * @throws OptionsException
     * @throws Exception
     */
    public function consume(string $subscriptionId, callable $callback, string $topicRegex = '*'): void
    {
        $tenant = $this->getPulsarTenant();
        $namespace = $this->getPulsarNamespace();
        $topicRegex = 'event-action-' . $topicRegex;
        //$topicRegex = '.*';

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

        $consumer = $this->client()->subscribeWithRegex("persistent://$tenant/$namespace/$topicRegex", $subscriptionId, $config);

        while (true) {
            $message = $consumer->receive();
            try {
                $message->getSchemaValue($data);
                $data = (array) $data;


                /** @var User */
                $user = $this->entitiesBuilder->single($data['user_guid']);

                // If no user, something went wrong, but still skip
                if (!$user || !$user instanceof User) {
                    $consumer->ack($message);
                    continue;
                }

                /** @var Entity */
                $entity = $this->entitiesResolver->single(new Urn($data['entity_urn']));

                // If no entity, skip as its unavailable
                if (!$entity) {
                    error_log('invalid entity ');
                    var_dump($data);
                    $consumer->ack($message);
                    continue;
                }

                $event = new ActionEvent();
                $event->setUser($user)
                    ->setEntity($entity)
                    ->setAction($data['action'])
                    ->setActionData($data['action_data'])
                    ->setTimestamp($message->getProperties()['event_timestamp']);



                $event->onForceAcknowledge(function () use ($consumer, $message) {
                    $consumer->ack($message);
                });

                if (call_user_func($callback, $event, $message) === true) {
                    $consumer->ack($message);
                } else {
                    throw new Exception("Failed to process message");
                }
            } catch (Exception $e) {
                $consumer->nack($message);
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
            'name' => 'action',
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
            ]
        ]);
    }
}
