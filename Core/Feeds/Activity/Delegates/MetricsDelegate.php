<?php

namespace Minds\Core\Feeds\Activity\Delegates;

use Minds\Core;
use Minds\Core\Analytics\Metrics\Event;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\Activity\InteractionCounters;
use Minds\Core\Wire\Paywall\PaywallEntityInterface;
use Minds\Entities\Activity;
use Minds\Helpers\Counters;

class MetricsDelegate
{
    /** @var InteractionCounters */
    protected $interactionCounters;

    public function __construct(InteractionCounters $interactionCounters = null)
    {
        $this->interactionCounters = $interactionCounters ?? Di::_()->get('Feeds\Activity\InteractionCounters');
    }

    /**
     * On adding a new post
     * @param Activity $activity
     * @return void
     */
    public function onAdd(Activity $activity): void
    {
        if ($activity->isRemind() || $activity->isQuotedPost()) {
            $remind = $activity->getRemind();

            // Submit to events engine

            $event = new Event();
            $event->setType('action')
                ->setAction('remind')
                ->setProduct('platform')
                ->setUserGuid((string) $activity->getOwnerGuid())
                ->setUserPhoneNumberHash(Core\Session::getLoggedInUser()->getPhoneNumberHash())
                ->setEntityGuid((string) $remind->getGuid())
                ->setEntityContainerGuid((string) $remind->getContainerGuid())
                ->setEntityType($remind->getType())
                ->setEntitySubtype((string) $remind->getSubtype())
                ->setEntityOwnerGuid((string) $remind->getOwnerGuid());

            if ($remind instanceof PaywallEntityInterface) {
                $wireThreshold = $remind->getWireThreshold();
                if ($wireThreshold['support_tier'] ?? null) {
                    $event->setSupportTierUrn($wireThreshold['support_tier']['urn']);
                }
            }

            $event->push();

            if ($activity->isQuotedPost()) {
                // Purge counter cache
                $quotesCounter = $this->interactionCounters->setCounter(InteractionCounters::COUNTER_QUOTES);
                $currentCount = (int) $quotesCounter->get($remind, readFromCache: false, saveToCache: false); // $redmind = quote post too.
                // hacky solution, increment the cache. purging the cache will show incorrect results
                // as it needs to wait for refresh_interval to clear on elasticsearch, post indexing
                $quotesCounter->updateCache($remind, $currentCount + 1);
            }
        }

        if ($activity->isRemind() && isset($remind)) {
            // Update remind counters (legacy support)
            Counters::increment($remind->getGuid(), 'remind');
        }
    }

    /**
     * On activity deleted
     * @param Activity $activity
     * @return void
     */
    public function onDelete(Activity $activity): void
    {
        if ($activity->isRemind()) {
            $remind = $activity->getRemind();
            if (!$remind) {
                return; // Original post may have been deleted too
            }
            // Update remind counters (legacy support)
            Counters::decrement($remind->getGuid(), 'remind');
        }
    }
}
