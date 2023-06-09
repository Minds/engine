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
    private const TOPIC = "captioned-activity";

    /**
     * @param EventInterface $event
     * @return bool
     */
    public function send(EventInterface $event): bool
    {
        if (php_sapi_name() === 'cli') {
            return false;
        }

        if (!$event instanceof CaptionedActivityEvent) {
            return false;
        }

        $producer = $this->getProducer();

        $producer->send(
            (new MessageBuilder())
                ->setEventTimestamp($event->getTimestamp() ?: time())
                ->setContent(json_encode([
                    'activity_urn' => $event->getActivityUrn(),
                    'guid' => $event->getGuid(),
                    'type' => $event->getType(),
                    'caption' => $event->getCaption(),
                ]))
        );

        return true;
    }

    private function getProducer(): Producer
    {
        return $this->client()->createProducer(
            "persistent://{$this->getPulsarTenant()}/{$this->getPulsarNamespace()}/" . self::TOPIC,
            (new ProducerConfiguration())
                ->setSchema(SchemaType::AVRO, "captioned-activity", $this->getSchema())
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
                $captionedActivity->setContainerGuid($data->container_guid);
                $captionedActivity->setOwnerGuid($data->owner_guid);
                $captionedActivity->setAccessId($data->access_id);
                $captionedActivity->setTimeCreated($data->time_created);
                $captionedActivity->setTimePublished($data->time_published);
                $captionedActivity->setTags($data->tags);
                $captionedActivity->setMessage($data->message);
                $captionedActivity->setCaption($data->caption);


                if (call_user_func($callback, $message) === false) {
                    $consumer->negativeAcknowledge($message);
                    continue;
                }
                $consumer->acknowledge($message);
            } catch (Exception $e) {
                $consumer->negativeAcknowledge($message);
            }
        }
    }

    private function getConsumer(string $subscriptionId): Consumer
    {
        return $this->client->subscribeWithRegex(
            "persistent://{$this->getPulsarTenant()}/{$this->getPulsarNamespace()}/" . self::TOPIC,
            $subscriptionId,
            (new ConsumerConfiguration())
                ->setConsumerType(Consumer::ConsumerShared)
                ->setSchema(SchemaType::AVRO, "captioned-activity", $this->getSchema(), [])
        );
    }

    /**
     * @return string
     */
    private function getSchema(): string
    {
        return json_encode([
            'type' => 'record',
            'name' => 'captioned-activity',
            'namespace' => $this->getPulsarNamespace(),
            'fields' => [
                [
                    'name' => 'activity_urn',
                    'type' => 'string'
                ],
                [
                    'name' => 'guid',
                    'type' => 'int'
                ],
                [
                    'name' => 'type',
                    'type' => 'string'
                ],
                [
                    'name' => 'container_guid',
                    'type' => 'int'
                ],
                [
                    'name' => 'owner_guid',
                    'type' => 'int'
                ],
                [
                    'name' => 'access_id',
                    'type' => 'int'
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
