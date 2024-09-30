<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\EventStreams;

use Exception;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Events\TenantBootstrapRequestEvent;
use Minds\Core\EventStreams\SubscriptionInterface;
use Minds\Core\EventStreams\Topics\TenantBootstrapRequestsTopic;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Bootstrap\Services\MultiTenantBootstrapService;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Pulsar subscription for tenant bootstrap requests.
 */
class TenantBootstrapRequestSubscription implements SubscriptionInterface
{
    public function __construct(
        private ?MultiTenantBootstrapService $multiTenantBootstrapService = null,
        private ?Logger $logger = null
    ) {
        $this->multiTenantBootstrapService ??= Di::_()->get(MultiTenantBootstrapService::class);
        $this->logger ??= Di::_()->get('Logger');
    }

    public function getSubscriptionId(): string
    {
        return 'tenant-bootstrap-requests-subscription';
    }

    public function getTopic(): TenantBootstrapRequestsTopic
    {
        return new TenantBootstrapRequestsTopic();
    }

    public function getTopicRegex(): string
    {
        return TenantBootstrapRequestsTopic::TOPIC;
    }

    /**
     * @param EventInterface $event
     * @return bool
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function consume(EventInterface $event): bool
    {
        // Acknowledge message so that it does not get held up in the message queue.
        if (!$event instanceof TenantBootstrapRequestEvent) {
            return true;
        }

        try {
            $this->multiTenantBootstrapService->bootstrap(
                $event->getSiteUrl(),
                $event->getTenantId()
            );
        } catch (\Exception $e) {
            $this->logger->error("Error bootstrapping tenant {$event->getTenantId()}: {$e->getMessage()}", [
                'exception' => $e,
            ]);
            return true;
        }

        return true;
    }
}
