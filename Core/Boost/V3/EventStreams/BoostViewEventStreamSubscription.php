<?php

namespace Minds\Core\Boost\V3\EventStreams;

use Exception;
use Minds\Common\Urn;
use Minds\Core\Boost\V3\Summaries\Manager;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\BatchSubscriptionInterface;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Topics\ViewsTopic;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;

/**
 * Pulsar consumer subscription responsible to process boost activity views
 */
class BoostViewEventStreamSubscription implements BatchSubscriptionInterface
{
    private static ?ViewsTopic $topic = null;

    public function __construct(
        private ?Manager $manager = null,
        private ?Logger $logger = null,
        protected ?MultiTenantBootService $multiTenantBootService = null
    ) {
        $this->manager ??= Di::_()->get(Manager::class);
        $this->logger ??= Di::_()->get('Logger');
        $this->multiTenantBootService ??= Di::_()->get(MultiTenantBootService::class);
    }

    /**
     * @inheritDoc
     */
    public function getSubscriptionId(): string
    {
        return 'boost-views';
    }

    /**
     * @inheritDoc
     */
    public function getTopic(): ViewsTopic
    {
        return self::$topic ?? self::$topic = new ViewsTopic();
    }

    /**
     * @inheritDoc
     */
    public function getTopicRegex(): string
    {
        return '*';
    }

    /**
     * @inheritDoc
     */
    public function consume(EventInterface $event): bool
    {
        return true;
    }

    /**
     * Process a batch of views
     * @param array $messages
     * @return bool
     * @throws Exception
     */
    public function consumeBatch(array $messages): bool
    {
        foreach ($messages as $message) {
            $messageData = json_decode($message->getDataAsString());

            if (!str_starts_with($messageData->cm_campaign, "urn:boost:")) {
                $this->getTopic()->markMessageAsProcessed($message);
                continue;
            }

            $tenantId = $messageData->tenant_id;
            $boostGuid = Urn::_($messageData->cm_campaign)->getNss();

            $this->logger->info("--------------------");
            $this->logger->info(
                "Start processing boost view event for boost $boostGuid and tenantId $tenantId",
                (array) $messageData
            );

            $isMessageProcessed = $this->manager->incrementViews(
                tenantId: $tenantId,
                boostGuid: $boostGuid,
                unixTimestamp: $message->getEventTimestamp()
            );

            $this->logger->info("Done processing boost view event", (array) $messageData);

            if (!$isMessageProcessed) {
                continue;
            }

            $this->getTopic()->markMessageAsProcessed($message);
            $this->logger->info("Marked boost  view event as processed", (array) $messageData);
            $this->logger->info("--------------------");
        }

        return true;
    }

    /**
     * Commits the db transaction containing all the successfully processed views
     * @return void
     */
    public function onBatchConsumed(): void
    {
        $this->manager->flush();
    }
}
