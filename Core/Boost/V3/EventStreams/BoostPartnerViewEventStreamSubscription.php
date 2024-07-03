<?php

namespace Minds\Core\Boost\V3\EventStreams;

use Exception;
use Minds\Common\Urn;
use Minds\Core\Boost\V3\Partners\Manager;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\BatchSubscriptionInterface;
use Minds\Core\EventStreams\EventInterface;
use Minds\Core\EventStreams\Topics\ViewsTopic;
use Minds\Core\Log\Logger;

/**
 * Pulsar consumer subscription responsible to process boost partner activity views
 */
class BoostPartnerViewEventStreamSubscription implements BatchSubscriptionInterface
{
    private static ?ViewsTopic $topic = null;

    public function __construct(
        private ?Manager $manager = null,
        private ?Logger $logger = null
    ) {
        $this->manager ??= Di::_()->get(Manager::class);
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * @inheritDoc
     */
    public function getSubscriptionId(): string
    {
        return 'boost-partners-views';
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
        $this->manager->beginTransaction();

        foreach ($messages as $message) {
            $messageData = json_decode($message->getDataAsString());

            if (!str_starts_with($messageData->cm_campaign, "urn:boost:") || $messageData->cm_served_by_guid === null) {
                $this->getTopic()->markMessageAsProcessed($message);
                continue;
            }

            if (!$messageData->user_guid) {
                $this->getTopic()->markMessageAsProcessed($message);
                continue;
            }

            $boostGuid = Urn::_($messageData->cm_campaign)->getNss();

            $this->logger->info("--------------------");
            $this->logger->info(
                "Start processing boost partner view event served by $messageData->cm_served_by_guid for boost $boostGuid",
                (array) $messageData
            );

            $isMessageProcessed = $this->manager->recordBoostPartnerView(
                userGuid: $messageData->cm_served_by_guid,
                boostGuid: $boostGuid,
                eventTimestamp: $message->getEventTimestamp()
            );

            $this->logger->info("Done processing boost partner view event", (array) $messageData);

            if (!$isMessageProcessed) {
                continue;
            }

            $this->getTopic()->markMessageAsProcessed($message);
            $this->logger->info("Marked boost partner view event as processed", (array) $messageData);
            $this->logger->info("--------------------");
        }

        if ($this->getTopic()->getTotalMessagesProcessedInBatch() === 0) {
            $this->manager->rollbackTransaction();
        }

        return true;
    }

    /**
     * Commits the db transaction containing all the successfully processed views
     * @return void
     */
    public function onBatchConsumed(): void
    {
        $this->manager->commitTransaction();
    }
}
