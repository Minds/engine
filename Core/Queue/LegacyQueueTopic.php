<?php
/**
 * This is the topic where all entity create, update and delete operations are processed.
 */
namespace Minds\Core\Queue;

use Minds\Core\Di\Di;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Topics\AbstractTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\EventStreams\UndeliveredEventException;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Pulsar\Consumer;
use Pulsar\ConsumerConfiguration;
use Pulsar\MessageBuilder;
use Pulsar\Producer;
use Pulsar\ProducerConfiguration;
use Pulsar\Result;
use Pulsar\SchemaType;

class LegacyQueueTopic extends AbstractTopic implements TopicInterface
{
    /** @var string */
    const TOPIC_NAME = 'legacy-queue';

    /** @var string */
    const SCHEMA_NAME = 'legacyqueue'; // AVRO schema dislikes hyphens

    /** @var Producer */
    protected $producer;

    protected MultiTenantBootService $multiTenantBootService;

    /**
     * Sends notifications events to our stream
     * @param EventInterface $event
     */
    public function send(EventInterface $event): bool
    {
        if (!$event instanceof Message) {
            return false;
        }

        $tenant = $this->getPulsarTenant();
        $namespace = $this->getPulsarNamespace();
        $topic = 'legacy-queue-' . strtolower($event->queueName);

        // Build the config and include the schema

        $config = new ProducerConfiguration();
        $config->setSchema(SchemaType::AVRO, self::SCHEMA_NAME, $this->getSchema(), []);

        $producer = $this->client()->createProducer("persistent://$tenant/$namespace/$topic", $config);

        // Build the message

        $data = [
            'data' => json_encode($event->data),
        ];

        if ($tenantId = $event->tenantId) {
            $data['tenant_id'] = $tenantId;
        }

        $builder = new MessageBuilder();

        if ($delaySecs = $event->delaySecs) {
            $builder->setDeliverAfter($delaySecs * 1000);
        }

        $message = $builder
            ->setEventTimestamp($event->getTimestamp() ?: time())
            ->setContent(json_encode($data))
            ->build();

        // Send the event to the stream

        $result = $producer->send($message);

        if ($result != Result::ResultOk) {
            throw new UndeliveredEventException();
        }

        return true;
    }

    /**
     * Consume stream events. Use a new $subscriptionId per service
     * eg. push, emails
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

        $config = new ConsumerConfiguration();
        $config->setConsumerType(Consumer::ConsumerShared);
        $config->setSchema(SchemaType::AVRO, static::SCHEMA_NAME, $this->getSchema(), []);

        $topic = 'legacy-queue-' . strtolower($topicRegex);

        $consumer = $this->client()->subscribe("persistent://$tenant/$namespace/$topic", $subscriptionId, $config);

        while (true) {
            try {
                $message = $consumer->receive();
                $eventData = json_decode($message->getDataAsString(), true);

                $data = json_decode($eventData['data'], true);

                $event = new Message(
                    queueName: $topicRegex,
                    data: $data,
                    delaySecs: 0,
                    tenantId: $eventData['tenant_id'],
                );
                $event->setTimestamp($message->getEventTimestamp());

                if (call_user_func($callback, $event, $message) === true) {
                    $consumer->acknowledge($message);
                }
            } catch (\Exception $e) {
                $this->logger->error("Topic(Consume): Uncaught error: " . $e->getMessage());
            }
        }
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
                    'name' => 'data',
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
