<?php
declare(strict_types=1);

namespace Minds\Core\EventStreams\Topics;

use Minds\Core\EventStreams\EventInterface;

class TestEventsTopic extends AbstractTopic implements TopicInterface
{

    public function send(EventInterface $event): bool
    {
        $tenant = $this->getPulsarTenant();
        $namespace = $this->getPulsarNamespace();
        $topic = 'event-action-' . $event->getAction();

        // Build the config and include the schema

        $config = new ProducerConfiguration();
        $config->setSchema(SchemaType::AVRO, "action", $this->getSchema(), []);

        $producer = $this->client()->createProducer("persistent://$tenant/$namespace/$topic", $config);

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
        return json_encode([]);
    }
}
