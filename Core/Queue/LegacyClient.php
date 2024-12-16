<?php

/**
 * Legacy Client with Pulsar
 *
 * @author emi
 */

namespace Minds\Core\Queue;

use Minds\Core\Di\Di;
use Minds\Core\Queue\Interfaces\QueueClient;
use Minds\Core\Queue\Message;
use Minds\Core\Config\Config;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\Queue\LegacyQueueTopic;

class LegacyClient implements QueueClient
{
    protected $queueName = 'default';

    protected ?MultiTenantBootService $multiTenantBootService;

    public function __construct(
        private Config $config,
        private Logger $logger,
        private ?LegacyQueueTopic $topic = null,
    ) {
    }

    public function setQueue($name = 'default'): self
    {
        $this->queueName = $name;

        return $this;
    }

    public function send(array $message, $delaySecs = 0): bool
    {
        $event = new Message(
            queueName: $this->queueName,
            data: $message,
            delaySecs: (int) $delaySecs,
            tenantId: $this->config->get('tenant_id'),
        );

        return $this->getTopic()->send($event);
    }

    public function receive($callback, $opts = [])
    {
        $this->getTopic()->consume(
            subscriptionId: $this->queueName,
            callback: function (EventInterface $event) use ($callback) {
                if (!$event instanceof Message) {
                    return true;
                }

                $return = true;

                // If multi tenant, load its configs
                if ($tenantId = $event->tenantId) {
                    $this->getMultiTenantBootService()->bootFromTenantId($tenantId);
                }

                try {
                    if ($callback($event) === false) {
                        $return = false; // Negative awknowledge
                    }
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                }

                // Reset multi tenant configs
                if ($tenantId) {
                    $this->getMultiTenantBootService()->resetRootConfigs();
                }

                return $return;
            },
            topicRegex: $this->queueName
        );
    }

    private function getTopic(): LegacyQueueTopic
    {
        return $this->topic ??= new LegacyQueueTopic();
    }

    private function getMultiTenantBootService(): MultiTenantBootService
    {
        return $this->multiTenantBootService ??= Di::_()->get(MultiTenantBootService::class);
    }
}
