<?php
declare(strict_types=1);

namespace Minds\Core\EventStreams\Topics;

use Exception;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Events\CaptionedActivityEvent;
use Pulsar\Consumer;
use Pulsar\ConsumerConfiguration;
use Pulsar\MessageBuilder;
use Pulsar\Producer;
use Pulsar\ProducerConfiguration;
use Pulsar\SchemaType;

class CaptionedActivitiesTopic extends AbstractTopic implements TopicInterface
{
    private const TOPIC = "captioned-activities";

    /**
     * @param EventInterface $event
     * @return bool
     */
    public function send(EventInterface $event): bool
    {
        if (php_sapi_name() !== 'cli') {
            return false;
        }

        if (!$event instanceof CaptionedActivityEvent) {
            return false;
        }

        $producer = $this->getProducer();

        $result = $producer->send(
            (new MessageBuilder())
                ->setEventTimestamp($event->getTimestamp() ?: time())
                ->setContent(json_encode([
                    'activity_urn' => $event->getActivityUrn(),
                    'guid' => $event->getGuid(),
                    'type' => $event->getType(),
                    'caption' => $event->getCaption(),
                ]))
        );

        return !$result;
    }

    private function getProducer(): Producer
    {
        return $this->client()->createProducer(
            "persistent://{$this->getPulsarTenant()}/{$this->getPulsarNamespace()}/" . self::TOPIC,
            (new ProducerConfiguration())
                ->setSchema(SchemaType::JSON, "captioned-activities", $this->getSchema())
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
                $captionedActivity = new CaptionedActivityEvent();
                $captionedActivity->setActivityUrn($data->activity_urn);
                $captionedActivity->setGuid($data->guid);
                $captionedActivity->setType($data->type);
                $captionedActivity->setContainerGuid(property_exists($data, 'container_guid') ? $data->container_guid : null);
                $captionedActivity->setOwnerGuid(property_exists($data, 'owner_guid') ? $data->owner_guid : null);
                $captionedActivity->setAccessId(property_exists($data, 'access_id') ? $data->access_id : null);
                $captionedActivity->setTimeCreated(property_exists($data, 'time_created') ? $data->time_created : null);
                $captionedActivity->setTimePublished(property_exists($data, 'time_published') ? $data->time_published : null);
                $captionedActivity->setTags(property_exists($data, 'tags') ? $data->tags : null);
                $captionedActivity->setMessage(property_exists($data, 'message') ? $data->message : null);
                $captionedActivity->setCaption($data->caption);

                $this->logger->info("", [
                    'activity_urn' => $captionedActivity->getActivityUrn(),
                    'guid' => $captionedActivity->getGuid(),
                    'type' => $captionedActivity->getType(),
                    'caption' => $captionedActivity->getCaption(),
                ]);


                if (call_user_func($callback, $captionedActivity) === false) {
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
                ->setSchema(SchemaType::JSON, "captioned-activities", $this->getSchema(), [])
        );
    }

    /**
     * @return string
     */
    private function getSchema(): string
    {
        return json_encode([
            'type' => 'record',
            'name' => 'ImageCaption',
            'namespace' => $this->getPulsarNamespace(),
            'fields' => [
                [
                    'name' => 'activity_urn',
                    'type' => 'string'
                ],
                [
                    'name' => 'guid',
                    'type' => 'long'
                ],
                [
                    'name' => 'type',
                    'type' => 'string'
                ],
                [
                    'name' => 'container_guid',
                    'type' => 'long'
                ],
                [
                    'name' => 'owner_guid',
                    'type' => 'long'
                ],
                [
                    'name' => 'access_id',
                    'type' => 'long'
                ],
                [
                    'name' => 'time_published',
                    'type' => 'string'
                ],
                [
                    'name' => 'time_created',
                    'type' => 'string'
                ],
                [
                    'name' => 'tags',
                    'type' => 'string'
                ],
                [
                    'name' => 'message',
                    'type' => 'string'
                ],
                [
                    'name' => 'caption',
                    'type' => 'string'
                ],
            ]
        ]);
    }
}
