<?php
declare(strict_types=1);

namespace Minds\Core\EventStreams\Topics;

use Exception;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Events\InferredTagEvent;
use Pulsar\Consumer;
use Pulsar\ConsumerConfiguration;
use Pulsar\MessageBuilder;
use Pulsar\Producer;
use Pulsar\ProducerConfiguration;
use Pulsar\SchemaType;

class InferredTagsTopic extends AbstractTopic implements TopicInterface
{
    private const TOPIC = "inferred-tags";

    /**
     * @param EventInterface $event
     * @return bool
     */
    public function send(EventInterface $event): bool
    {
        if (php_sapi_name() !== 'cli') {
            return false;
        }

        if (!$event instanceof InferredTagEvent) {
            return false;
        }

        $producer = $this->getProducer();

        $result = $producer->send(
            (new MessageBuilder())
                ->setEventTimestamp($event->getTimestamp() ?: time())
                ->setContent(json_encode([
                    'activity_urn' => $event->activityUrn,
                    'guid' => $event->guid,
                    'embed_string' => $event->embedString,
                    'inferred_tags' => json_encode($event->inferredTags),
                ]))
        );

        return !$result;
    }

    private function getProducer(): Producer
    {
        return $this->client()->createProducer(
            "persistent://{$this->getPulsarTenant()}/{$this->getPulsarNamespace()}/" . self::TOPIC,
            (new ProducerConfiguration())
                ->setSchema(SchemaType::JSON, "inferred-tags", $this->getSchema())
        );
    }

    /**
     * @param string $subscriptionId
     * @param callable $callback
     * @param string $topicRegex
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
        $consumer = $this->getConsumer($subscriptionId);

        $this->process($consumer, $callback);
    }

    private function process(Consumer $consumer, callable $callback): void
    {
        while (true) {
            $message = $consumer->receive();
            try {
                $this->logger->info("Received message");
                $data = json_decode($message->getDataAsString());

                // Map data to CaptionedActivity object
                $inferredTagsEvent = new InferredTagEvent(
                    activityUrn: $data->activity_urn,
                    guid: $data->guid,
                    embedString: $data->embed_string,
                    inferredTags: $data->inferred_tags
                );

                $this->logger->info("", [
                    'activity_urn' => $inferredTagsEvent->activityUrn,
                    'guid' => $inferredTagsEvent->guid,
                    'embed_string' => $inferredTagsEvent->embedString,
                    'inferred_tags' => $inferredTagsEvent->inferredTags,
                ]);


                if (call_user_func($callback, $inferredTagsEvent) === false) {
                    $this->logger->info("Negative acknowledging message");
                    $consumer->negativeAcknowledge($message);
                    continue;
                }
                $this->logger->info("Acknowledging message");
                $consumer->acknowledge($message);
            } catch (Exception $e) {
                $consumer->negativeAcknowledge($message);
            }
        }
    }

    /**
     * @param string $subscriptionId
     * @return Consumer
     */
    private function getConsumer(string $subscriptionId): Consumer
    {
        return $this->client()->subscribeWithRegex(
            "persistent://{$this->getPulsarTenant()}/{$this->getPulsarNamespace()}/" . self::TOPIC,
            $subscriptionId,
            (new ConsumerConfiguration())
                ->setConsumerType(Consumer::ConsumerShared)
                ->setSchema(SchemaType::JSON, "inferred-tags", $this->getSchema(), [])
        );
    }

    /**
     * @return string
     */
    private function getSchema(): string
    {
        return json_encode([
            'type' => 'record',
            'name' => 'InferredTags',
            'namespace' => $this->getPulsarNamespace(),
            'fields' => [
                [
                    'name' => 'activity_urn',
                    'type' => [ 'null', 'string' ],
                ],
                [
                    'name' => 'guid',
                    'type' => [ 'null', 'long' ],
                ],
                [
                    'name' => 'embed_string',
                    'type' => [ 'null', 'string' ],
                ],
                [
                    'name' => 'inferred_tags',
                    'type' => [ 'null', 'string' ],
                ],
            ]
        ]);
    }
}
