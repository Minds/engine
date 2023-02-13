<?php
declare(strict_types=1);

namespace Minds\Core\EventStreams\Topics;

use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Events\ViewEvent;
use Pulsar\Producer;
use Pulsar\ProducerConfiguration;
use Pulsar\SchemaType;

class ViewsTopic extends AbstractTopic implements TopicInterface
{
    public const TOPIC = "event-view";

    /**
     * @inheritDoc
     */
    public function send(EventInterface $event): bool
    {
        if (!($event instanceof ViewEvent)) {
            return false;
        }

        $producer = $this->getProducer();
    }

    private function getProducer(): Producer
    {
        return $this->client()->createProducer(
            "persistent://{$this->getPulsarTenant()}/{$this->getPulsarNamespace()}/{self::TOPIC}",
            (new ProducerConfiguration())
                ->setSchema(SchemaType::AVRO, "view", $this->getSchema())
        );
    }

    /**
     * @inheritDoc
     */
    public function consume(string $subscriptionId, callable $callback): void
    {
        // TODO: Implement consume() method.
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
                    'type' => 'integer'
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
                    'type' => 'integer'
                ],
                [
                    'name' => 'cm_position',
                    'type' => 'integer'
                ],
                [
                    'name' => 'cm_served_by_guid',
                    'type' => 'string'
                ],
            ]
        ]);
    }
}
