<?php
declare(strict_types=1);

namespace Minds\Core\EventStreams\Topics;

use Minds\Core\EventStreams\EventInterface;
use Pulsar\MessageBuilder;
use Pulsar\ProducerConfiguration;
use Pulsar\Result;
use Pulsar\SchemaType;

class TestEventsTopic extends AbstractTopic implements TopicInterface
{

    public function send(?EventInterface $event = null): bool
    {
        $tenant = $this->getPulsarTenant();
        $namespace = $this->getPulsarNamespace();
        $topic = 'test-php-topic';

        // Build the config and include the schema

        $config = new ProducerConfiguration();
        $config->setSchema(SchemaType::AVRO, "test_php_80", $this->getSchema(), []);

        $producer = $this->client()->createProducer("persistent://$tenant/$namespace/$topic", $config);

        $message = (new MessageBuilder())
            ->setEventTimestamp(time())
            ->setContent(json_encode([
                'message_body' => 'test message'
            ]))
            ->build();

        $result = $producer->send($message);

        return $result == Result::ResultOk;
    }

    public function consume(
        string $subscriptionId,
        callable $callback,
        string $topicRegex = '*',
        bool $isBatch = false,
        int $batchTotalAmount = 1,
        int $execTimeoutInSeconds = 30,
        ?callable $onBatchConsumed = null
    ): void {
    }

    private function getSchema(): string
    {
        return json_encode([
            'type' => 'record',
            'name' => 'test_php_80',
            'namespace' => 'engine',
            'fields' => [
                [
                    'name' => 'message_body',
                    'type' => 'string'
                ]
            ]
        ]);
    }
}
