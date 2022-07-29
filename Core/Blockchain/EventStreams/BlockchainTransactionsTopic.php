<?php

namespace Minds\Core\Blockchain\EventStreams;

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

/**
 * Topic for blockchain transactions.
 */
class BlockchainTransactionsTopic extends AbstractTopic implements TopicInterface
{
    /** @var string topic name */
    const TOPIC_NAME = 'blockchain-transactions';

    /** @var string schema name - note that AVRO schema dislikes hyphens */
    const SCHEMA_NAME = 'blockchaintransactions';

    /** @var Producer - producer */
    protected $producer;

    /**
     * Constructor.
     * @param Client|null $client - pulsar client.
     * @param Config|null $config - config.
     */
    public function __construct(
        Client $client = null,
        Config $config = null
    ) {
        $this->client = $client ?? null;
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * Sends blockchain transaction events to our stream.
     * @param BlockchainTransactionEvent $event - event to send.
     * @return bool
     */
    public function send(EventInterface $event): bool
    {
        if (!$event instanceof BlockchainTransactionEvent) {
            return false;
        }

        // Build the message

        $builder = new MessageBuilder();
        $message = $builder
            //->setPartitionKey(0)
            ->setEventTimestamp($event->getTimestamp() ?: time())
            ->setContent(json_encode([
                'sender_guid' => $event->getSenderGuid(),
                'receiver_guid' => $event->getReceiverGuid(),
                'transaction_id' => $event->getTransactionId(),
                'skale_transaction_id' => $event->getSkaleTransactionId(),
                'wallet_address' => $event->getWalletAddress(),
                'contract' => $event->getContract(),
                'amount_wei' => $event->getAmountWei()
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
     * Consume stream events. Use a new $subscriptionId per service.
     * @param string $subscriptionId - subscription id.
     * @param callable $callback - the logic for the event.
     * @param string $topicRegex - defaults to * (all topics will be returned).
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

                $event = new BlockchainTransactionEvent();

                $event->setSenderGuid($data['sender_guid'])
                    ->setReceiverGuid($data['receiver_guid'])
                    ->setTransactionId($data['transaction_id'])
                    ->setSkaleTransactionId($data['skale_transaction_id'])
                    ->setWalletAddress($data['wallet_address'])
                    ->setContract($data['contract'])
                    ->setAmountWei($data['amount_wei'])
                    ->setTimestamp($message->getEventTimestamp());

                if (call_user_func($callback, $event, $message) === true) {
                    $consumer->acknowledge($message);
                }
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * Gets producer,
     * @return Producer - producer.
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
     * so its it different to that of the topic.
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
                    'name' => 'sender_guid',
                    'type' => 'string',
                ],
                [
                    'name' => 'receiver_guid',
                    'type' => 'string',
                ],
                [
                    'name' => 'transaction_id',
                    'type' => 'string',
                ],
                [
                    'name' => 'skale_transaction_id',
                    'type' => 'string',
                ],
                [
                    'name' => 'wallet_address',
                    'type' => 'string'
                ],
                [
                    'name' => 'contract',
                    'type' => 'string'
                ],
                [
                    'name' => 'amount_wei',
                    'type' => 'string',
                ]
            ]
        ]);
    }
}
