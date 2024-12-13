<?php
/**
 * This is the topic where all entity create, update and delete operations are processed.
 */
namespace Minds\Core\Entities\Ops;

use Minds\Core\Di\Di;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Topics\AbstractTopic;
use Minds\Core\EventStreams\Topics\TopicInterface;
use Minds\Core\EventStreams\UndeliveredEventException;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Exceptions\NotFoundException;
use PDOException;
use Pulsar\Consumer;
use Pulsar\ConsumerConfiguration;
use Pulsar\MessageBuilder;
use Pulsar\Producer;
use Pulsar\ProducerConfiguration;
use Pulsar\Result;
use Pulsar\SchemaType;

class EntitiesOpsTopic extends AbstractTopic implements TopicInterface
{
    /** @var string */
    const TOPIC_NAME = 'entities-ops';

    /** @var string */
    const SCHEMA_NAME = 'entitiesops'; // AVRO schema dislikes hyphens

    /** @var Producer */
    protected $producer;

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

        $data = [
            'op' => $event->getOp(),
            'entity_urn' => $event->getEntityUrn(),
            'entity_serialized' => $event->getEntitySerialized(),
        ];

        if ($tenantId = $this->config->get('tenant_id')) {
            $data['tenant_id'] = $tenantId;
        }

        $builder = new MessageBuilder();
        $message = $builder
            //->setPartitionKey(0)
            ->setDeliverAfter($event->getDelaySecs())
            ->setEventTimestamp($event->getTimestamp() ?: time())
            ->setContent(json_encode($data))
            ->build();

        // Send the event to the stream

        $result = $this->getProducer()->send($message);

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

                // Multi tenant support

                if (isset($data['tenant_id']) && $tenantId = $data['tenant_id']) {
                    $this->getMultiTenantBootService()->bootFromTenantId($tenantId);
                }

                if (isset($data['entity_serialized'])) {
                    $event->setEntitySerialized($data['entity_serialized']);
                }

                if (call_user_func($callback, $event, $message) === true) {
                    $consumer->acknowledge($message);
                } else {
                    $consumer->negativeAcknowledge($message);
                }
            } catch (NotFoundException) {
                // The entity no longer exists, skip
                $consumer->acknowledge($message);
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
        $topic = static::TOPIC_NAME;

        // Build the config and include the schema

        $config = new ProducerConfiguration();
        $config->setSchema(SchemaType::AVRO, static::SCHEMA_NAME, $this->getSchema(), []);

        $schema = json_encode([
            'type' => 'AVRO',
            'schema' => $this->getSchema(),
            'properties' => (object) []
        ]);


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
                [
                    'name' => 'entity_json',
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
