<?php
/**
 * This is the topic where all entity create, update and delete operations are processed.
 */
namespace Minds\Core\Entities\Ops;

use Exception;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Topics\AbstractTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Pulsar\Client;
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

class EntitiesOpsTopic extends AbstractTopic implements TopicInterface
{
    /** @var string */
    const TOPIC_NAME = 'entities-ops';

    /** @var string */
    const SCHEMA_NAME = 'entitiesops'; // AVRO schema dislikes hyphens

    /** @var Producer */
    protected $producer;

    public function __construct(
        Client $client = null,
        Config $config = null
    ) {
        $this->client = $client ?? null;
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * Sends notifications events to our stream
     * @param EventInterface $event
     * @return bool
     * @throws IOException
     * @throws OptionsException
     * @throws RuntimeException
     */
    public function send(EventInterface $event): bool
    {
        if (!$event instanceof EntitiesOpsEvent) {
            return false;
        }

        // Build the message
        $message = (object) [
            'op' => $event->getOp(),
            'entity_urn' => $event->getEntityUrn(),
        ];

        try {
            // Send the event to the stream
            $this->getProducer()->send(
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
     * eg. push, emails
     * @param string $subscriptionId
     * @param callable $callback - the logic for the event
     * @param string $topicRegex - defaults to * (all topics will be returned)
     * @return void
     * @throws IOException
     * @throws MessageNotFound
     * @throws OptionsException
     * @throws RuntimeException
     * @throws Exception
     */
    public function consume(string $subscriptionId, callable $callback, string $topicRegex = '*'): void
    {
        $consumer = $this->getConsumer($subscriptionId);

        while (true) {
            $message = $consumer->receive();
            try {

                $message->getSchemaValue($data);
                $data = (array) $data;

                $event = new EntitiesOpsEvent();
                $event->setEntityUrn($data['entity_urn'])
                    ->setOp($data['op'])
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
    protected function getProducer(): Producer
    {
        if ($this->producer) {
            return $this->producer;
        }

        $tenant = $this->getPulsarTenant();
        $namespace = $this->getPulsarNamespace();
        $topic = static::TOPIC_NAME;

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

        return $this->producer = $this->client()->createProducer("persistent://$tenant/$namespace/$topic", $config);
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
        $topicRegex = static::TOPIC_NAME;

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

        return $this->client()->subscribeWithRegex("persistent://$tenant/$namespace/$topicRegex", $subscriptionId, $config);
    }

    /**
     * Return the schema
     * We use AvroSchem here. NOTE we can't use hyphens (-) in the schema name
     * so its it different to that of the topic
     * @return string
     */
    protected function getSchema(): string
    {
        return json_encode([
            'type' => 'record',
            'name' => static::SCHEMA_NAME,
            'namespace' => 'engine',
            'fields' => [
                [
                    'name' => 'op',
                    'type' => 'string',
                ],
                [
                    'name' => 'entity_urn',
                    'type' => 'string',
                ],
            ]
        ]);
    }
}
