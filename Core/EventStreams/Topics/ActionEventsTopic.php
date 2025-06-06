<?php
/**
 * All action events are submitted via this producer
 */
namespace Minds\Core\EventStreams\Topics;

use Minds\Common\Urn;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\UndeliveredEventException;
use Minds\Entities\Entity;
use Minds\Entities\User;
use Minds\Helpers\MagicAttributes;
use PDOException;
use Pulsar\Consumer;
use Pulsar\ConsumerConfiguration;
use Pulsar\MessageBuilder;
use Pulsar\ProducerConfiguration;
use Pulsar\Result;
use Pulsar\SchemaType;

class ActionEventsTopic extends AbstractTopic implements TopicInterface
{
    /**
     * Sends action events to our stream
     * @param EventInterface $event
     */
    public function send(EventInterface $event): bool
    {
        if (!$event instanceof ActionEvent) {
            return false;
        }

        $tenant = $this->getPulsarTenant();
        $namespace = $this->getPulsarNamespace();
        $topic = 'event-action-' . $event->getAction();

        // Build the config and include the schema

        $config = new ProducerConfiguration();
        $config->setSchema(SchemaType::AVRO, "action", $this->getSchema(), []);

        $producer = $this->client()->createProducer("persistent://$tenant/$namespace/$topic", $config);

        $user = $event->getUser();

        // Build the message

        $data = [
            'action' => $event->getAction(),
            'action_data' => $event->getActionData(),
            'user_guid' => $user ? (string) $event->getUser()->getGuid() : null,
            'entity_urn' => (string) $event->getEntity()->getUrn(),
            'entity_guid' => (string) $event->getEntity()->getGuid(),
            'entity_owner_guid' => (string) $event->getEntity()->getOwnerGuid(),
            'entity_type' => MagicAttributes::getterExists($event->getEntity(), 'getType') ? (string) $event->getEntity()->getType() : '',
            'entity_subtype' => MagicAttributes::getterExists($event->getEntity(), 'getSubtype') ? (string) $event->getEntity()->getSubtype() : '',
        ];

        if ($tenantId = $this->config->get('tenant_id')) {
            $data['tenant_id'] = $tenantId;
        }

        $builder = new MessageBuilder();
        $message = $builder
            //->setPartitionKey(0)
            ->setDeliverAfter($event->getDelayMs())
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
     * eg. notifications, analytics, recomendations
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
        $topicRegex = 'event-action-' . $topicRegex;
        //$topicRegex = '.*';

        $config = new ConsumerConfiguration();
        $config->setConsumerType(Consumer::ConsumerShared);
        $config->setSchema(SchemaType::AVRO, "action", $this->getSchema(), []);

        $consumer = $this->client()->subscribeWithRegex("persistent://$tenant/$namespace/$topicRegex", $subscriptionId, $config);

        while (true) {
            try {
                $message = $consumer->receive();
                $data = json_decode($message->getDataAsString(), true);

                // Multi tenant support

                if (isset($data['tenant_id']) && $tenantId = $data['tenant_id']) {
                    $this->getMultiTenantBootService()->bootFromTenantId($tenantId);
                }

                /** @var User */
                $user = $this->entitiesBuilder->single($data['user_guid']);

                // If no user, something went wrong, but still skip
                if ((!$user || !$user instanceof User) && $subscriptionId !== 'boost-clicks') {
                    $this->logger->warning('User ' . $data['user_guid'] . ' not found', $data);
                    $consumer->acknowledge($message);
                    continue;
                }

                /** @var Entity */
                $entity = $this->entitiesResolver->single(new Urn($data['entity_urn']));

                // If no entity, skip as its unavailable
                if (!$entity) {
                    error_log('invalid entity ');
                    var_dump($data);
                    $consumer->acknowledge($message);
                    continue;
                }

                $event = new ActionEvent();
                $event->setUser($user)
                    ->setEntity($entity)
                    ->setAction($data['action'])
                    ->setActionData($data['action_data'])
                    ->setTimestamp($message->getEventTimestamp());

                $event->onForceAcknowledge(function () use ($consumer, $message) {
                    $consumer->acknowledge($message);
                });

                if (call_user_func($callback, $event, $message) === true) {
                    $consumer->acknowledge($message);
                } else {
                    throw new \Exception("Failed to process message");
                }
            } catch (\Exception $e) {
                $consumer->negativeAcknowledge($message);
                $this->logger->error(
                    "Topic(Consume): Uncaught error: " . $e->getMessage(),
                    [
                    'exception' => $e
                ]
                );
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
     * Return the schema
     * @return string
     */
    protected function getSchema(): string
    {
        return json_encode([
            'type' => 'record', // ??
            'name' => 'action',
            'namespace' => 'engine',
            'fields' => [
                [
                    'name' => 'action',
                    'type' => 'string',
                ],
                [
                    'name' => 'action_data',
                    'type' => 'string',
                ],
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
                    'name' => 'tenant_id',
                    'type' => 'int'
                ],
            ]
        ]);
    }
}
