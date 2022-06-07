<?php
/**
 * This is the topic where all entity create, update and delete operations are processed.
 */
namespace Minds\Core\Entities\Ops;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Topics\AbstractTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Pulsar\Client;
use Pulsar\MessageBuilder;
use Pulsar\ProducerConfiguration;
use Pulsar\ConsumerConfiguration;
use Pulsar\Consumer;
use Pulsar\Producer;
use Pulsar\SchemaType;
use Pulsar\Result;

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
     */
    public function send(EventInterface $event): bool
    {
        if (!$event instanceof EntitiesOpsEvent) {
            return false;
        }

        // Build the message

        $builder = new MessageBuilder();
        $message = $builder
            //->setPartitionKey(0)
            ->setEventTimestamp($event->getTimestamp() ?: time())
            ->setContent(json_encode([
                'op' => $event->getOp(),
                'entity_urn' => $event->getEntityUrn(),
            ]))
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
        $topic = static::TOPIC_NAME;

        $config = new ConsumerConfiguration();
        $config->setConsumerType(Consumer::ConsumerShared);
        $config->setSchema(SchemaType::AVRO, static::SCHEMA_NAME, $this->getSchema(), []);

        $consumer = $this->client()->subscribe("persistent://$tenant/$namespace/$topic", $subscriptionId, $config);

        while (true) {
            try {
                $message = $consumer->receive();
                $data = json_decode($message->getDataAsString(), true);

                $event = new EntitiesOpsEvent();
                $event->setEntityUrn($data['entity_urn'])
                    ->setOp($data['op'])
                    ->setTimestamp($message->getEventTimestamp());

                if (call_user_func($callback, $event, $message) === true) {
                    $consumer->acknowledge($message);
                }
            } catch (\Exception $e) {
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
        $topic = static::TOPIC_NAME;

        // Build the config and include the schema

        $config = new ProducerConfiguration();
        $config->setSchema(SchemaType::AVRO, static::SCHEMA_NAME, $this->getSchema(), []);

        return $this->producer = $this->client()->createProducer("persistent://$tenant/$namespace/$topic", $config);
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
