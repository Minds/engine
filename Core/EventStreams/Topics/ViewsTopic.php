<?php
declare(strict_types=1);

namespace Minds\Core\EventStreams\Topics;

use Exception;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Events\ViewEvent;
use Minds\Helpers\MagicAttributes;
use Pulsar\Consumer;
use Pulsar\ConsumerConfiguration;
use Pulsar\Message;
use Pulsar\MessageBuilder;
use Pulsar\Producer;
use Pulsar\ProducerConfiguration;
use Pulsar\Result;
use Pulsar\SchemaType;

class ViewsTopic extends AbstractTopic implements TopicInterface
{
    public const TOPIC = "event-view";

    private static array $batchMessages = [];
    private static array $processedMessages = [];

    private static int $startTime = 0;

    /**
     * @inheritDoc
     */
    public function send(EventInterface $event): bool
    {
        if (!($event instanceof ViewEvent)) {
            return false;
        }

        $producer = $this->getProducer();

        return $producer->send($this->createMessage($event)) === Result::ResultOk;
    }

    private function getProducer(): Producer
    {
        return $this->client()->createProducer(
            "persistent://{$this->getPulsarTenant()}/{$this->getPulsarNamespace()}/" . self::TOPIC,
            (new ProducerConfiguration())
                ->setSchema(SchemaType::AVRO, "view", $this->getSchema())
        );
    }

    private function createMessage(ViewEvent $event): Message
    {
        return (new MessageBuilder())
            ->setEventTimestamp($event->getTimestamp() ?: time())
            ->setContent(
                json_encode([
                    'user_guid' => (string) $event->getUser()->getGuid(),
                    'entity_urn' => (string) $event->getEntity()->getUrn(),
                    'entity_guid' => (string) $event->getEntity()->getUrn(),
                    'entity_owner_guid' => (string) $event->getEntity()->getOwnerGuid(),
                    'entity_type' => MagicAttributes::getterExists($event->getEntity(), 'getType') ? (string) $event->getEntity()->getType() : '',
                    'entity_subtype' => MagicAttributes::getterExists($event->getEntity(), 'getSubtype') ? (string) $event->getEntity()->getSubtype() : '',
                    'cm_platform' => $event->cm_platform,
                    'cm_source' => $event->cm_source,
                    'cm_timestamp' => $event->cm_timestamp,
                    'cm_salt' => $event->cm_salt,
                    'cm_medium' => $event->cm_medium,
                    'cm_campaign' => $event->cm_campaign,
                    'cm_page_token' => $event->cm_page_token,
                    'cm_delta' => $event->cm_delta,
                    'cm_position' => $event->cm_position,
                    'cm_served_by_guid' => $event->cm_served_by_guid,
                ])
            )
            ->build();
    }

    /**
     * @inheritDoc
     */
    public function consume(string $subscriptionId, callable $callback, string $topicRegex = '*', int $batchTotalAmount = 1, int $execTimeoutInSeconds = 30): void
    {
        $consumer = $this->getConsumer($subscriptionId);

        while (true) {
            try {
                if (count(self::$batchMessages) < $batchTotalAmount) {
                    $message = $consumer->receive();
                    self::$batchMessages[$this->getMessageId($message)] = $message;
                }

                if (self::$startTime === 0) {
                    self::$startTime = time();
                } elseif ((time() - self::$startTime) >= $execTimeoutInSeconds) {
                    $this->acknowledgeProcessedMessages($consumer);
                    self::$startTime = 0;
                    continue;
                }

                if (call_user_func($callback, self::$batchMessages) === true) {
                    $this->acknowledgeProcessedMessages($consumer);
                    continue;
                }
            } catch (Exception $e) {
                $this->acknowledgeProcessedMessages($consumer);
            }
        }
    }

    private function acknowledgeProcessedMessages(Consumer $consumer): void
    {
        foreach (self::$processedMessages as $message) {
            $consumer->acknowledge($message);
            unset(self::$batchMessages[$this->getMessageId($message)]);
        }
    }

    private function getMessageId(Message $message): string
    {
        return hash('md5', (string) $message->getDataAsString());
    }

    private function resetProcessedMessages(): void
    {
        self::$processedMessages = [];
    }

    public function consumeBatch(string $subscriptionId, callable $callback, string $topicRegex = '*', int $batchTotalAmount = 10000, int $execTimeoutInSeconds = 30): void
    {
        $this->consume($subscriptionId, $callback, $topicRegex, $batchTotalAmount, $execTimeoutInSeconds);
    }

    private function getConsumer(string $subscriptionId): Consumer
    {
        return $this->client()->subscribeWithRegex(
            "persistent://{$this->getPulsarTenant()}/{$this->getPulsarNamespace()}/" . self::TOPIC,
            $subscriptionId,
            (new ConsumerConfiguration())
                ->setConsumerType(Consumer::ConsumerShared)
                ->setSchema(SchemaType::AVRO, "view", $this->getSchema(), [])
        );
    }

    private function getSchema(): string
    {
        return json_encode([
            'type' => 'record',
            'name' => 'view',
            'namespace' => $this->getPulsarNamespace(),
            'fields' => [
                [
                    'name' => 'user_guid',
                    'type' => 'string',
                ],
                [
                    'name' => 'entity_urn',
                    'type' => 'string',
                ],
                [
                    'name' => 'entity_guid',
                    'type' => 'string',
                ],
                [
                    'name' => 'entity_owner_guid',
                    'type' => 'string',
                ],
                [
                    'name' => 'entity_type',
                    'type' => 'string'
                ],
                [
                    'name' => 'entity_subtype',
                    'type' => 'string'
                ],
                [
                    'name' => 'cm_platform',
                    'type' => 'string'
                ],
                [
                    'name' => 'cm_source',
                    'type' => 'string'
                ],
                [
                    'name' => 'cm_timestamp',
                    'type' => 'string'
                ],
                [
                    'name' => 'cm_salt',
                    'type' => 'string'
                ],
                [
                    'name' => 'cm_medium',
                    'type' => 'string'
                ],
                [
                    'name' => 'cm_campaign',
                    'type' => 'string'
                ],
                [
                    'name' => 'cm_page_token',
                    'type' => 'string'
                ],
                [
                    'name' => 'cm_delta',
                    'type' => 'string'
                ],
                [
                    'name' => 'cm_position',
                    'type' => 'string'
                ],
                [
                    'name' => 'cm_served_by_guid',
                    'type' => 'string'
                ],
            ]
        ]);
    }

    public function markMessageAsProcessed(Message $message): void
    {
        self::$processedMessages[] = $message;
    }
}
