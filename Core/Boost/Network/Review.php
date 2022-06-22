<?php

namespace Minds\Core\Boost\Network;

use Minds\Core;
use Minds\Core\Data;
use Minds\Core\Di\Di;
use Minds\Entities\Boost\BoostEntityInterface;
use Minds\Entities\Boost\Network;
use Minds\Helpers\MagicAttributes;
use Minds\Interfaces\BoostReviewInterface;
use Minds\Core\Boost\Delegates;
use Minds\Core\Boost\Delegates\OnchainBadgeDelegate;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Sessions\ActiveSession;
use MongoDB\BSON;

class Review implements BoostReviewInterface
{
    /** @var  Network $boost */
    protected $boost;

    /** @var Manager $manager */
    protected $manager;

    /** @var OnchainBadgeDelegate $onchainBadge */
    protected $onchainBadge;

    /** @var Analytics $analaytics */
    protected $analytics;

    /** @var string $type */
    protected $type;

    /** @var ActionEventsTopic */
    protected $actionEventsTopic;

    /** @var ActiveSession */
    protected $activeSession;

    public function __construct(
        $manager = null,
        $analytics = null,
        $onchainBadge = null,
        ActionEventsTopic $actionEventsTopic = null,
        ActiveSession $activeSession = null
    ) {
        $this->manager = $manager ?: new Manager;
        $this->analytics = $analytics ?: new Analytics;
        $this->onchainBadge = $onchainBadge ?: new OnchainBadgeDelegate;
        $this->actionEventsTopic = $actionEventsTopic ?? Di::_()->get('EventStreams\Topics\ActionEventsTopic');
        $this->activeSession = $activeSession ?? Di::_()->get('Sessions\ActiveSession');
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = strtolower($type);
        return $this;
    }

    /**
     * @param Network $boost
     * @return $this
     */
    public function setBoost($boost)
    {
        $this->boost = $boost;
        return $this;
    }

    /**
     * Accept a boost
     * @return bool
     * @throws \Exception
     * @internal param mixed $_id
     * @internal param int $impressions Optional. Defaults to 0.
     */
    public function accept()
    {
        if (!$this->boost) {
            throw new \Exception('Boost wasn\'t set');
        }
        $success = Core\Di\Di::_()->get('Boost\Payment')->charge($this->boost);
        if ($success) {
            if ($this->boost->isOnChain()) {
                $this->onchainBadge->dispatch($this->boost);
            }
            $this->boost->setReviewedTimestamp(round(microtime(true) * 1000));

            $actionEvent = new ActionEvent();
            $actionEvent
                ->setAction(ActionEvent::ACTION_BOOST_ACCEPTED)
                ->setEntity($this->boost)
                ->setUser($this->activeSession->getUser());

            $this->actionEventsTopic->send($actionEvent);

            return $this->manager->update($this->boost);
        }
        throw new \Exception('Failed to charge for boost');
    }

    /**
     * Reject a boost
     * @param  int $reason
     * @throws \Exception
     */
    public function reject($reason)
    {
        if (!$this->boost) {
            throw new \Exception('Boost wasn\'t set');
        }

        $this->boost->setRejectedReason($reason);
        $this->boost->setReviewedTimestamp(round(microtime(true) * 1000));
        $this->boost->setRejectedTimestamp(round(microtime(true) * 1000));
        $this->manager->update($this->boost);

        $entity = $this->boost->getEntity();

        $dirty = $this->enableBoostRejectionReasonFlag($entity, $reason);

        Core\Events\Dispatcher::trigger('notification', 'boost', [
            'to' => [$this->boost->getOwner()->guid],
            'from' => 100000000000000519,
            'entity' => $this->boost->getEntity(),
            'params' => [
                'reason' => $this->boost->getRejectedReason(),
                'title' => $this->boost->getEntity()->title ?: $this->boost->getEntity()->message
            ],
            'notification_view' => 'boost_rejected',
        ]);

        //
        $actionEvent = new ActionEvent();
        $actionEvent
            ->setAction(ActionEvent::ACTION_BOOST_REJECTED)
            ->setActionData([
                'boost_reject_reason' => $this->boost->getRejectedReason(),
            ])
            ->setEntity($this->boost)
            ->setUser($this->activeSession->getUser());

        $this->actionEventsTopic->send($actionEvent);

        Core\Di\Di::_()->get('Boost\Payment')->refund($this->boost);

        if ($dirty) {
            $entity->save();
        }
    }

    /**
     * Revoke a boost
     * @return bool
     * @throws \Exception
     */
    public function revoke()
    {
        if (!$this->boost) {
            throw new \Exception('Boost wasn\'t set');
        }

        $this->boost->setRevokedTimestamp(round(microtime(true) * 1000));
        $this->manager->update($this->boost);

        Core\Events\Dispatcher::trigger('notification', 'boost', [
            'to' => [$this->boost->getOwner()->guid],
            'from' => 100000000000000519,
            'entity' => $this->boost->getEntity(),
            'params' => [
                'title' => $this->boost->getEntity()->title ?: $this->boost->getEntity()->message
            ],
            'notification_view' => 'boost_revoked',
        ]);
        return true;
    }

    /**
     * Gets a single boost entity
     * @param  mixed $guid
     * @return Boost
     */
    public function getBoostEntity($guid)
    {
        return $this->manager->get("urn:boost:{$this->type}:{$guid}", [ 'hydrate' => true ]);
    }

    protected function enableBoostRejectionReasonFlag($entity = null, $reason = -1)
    {
        if (!$entity || !is_object($entity)) {
            return false;
        }

        $dirty = false;

        // Main boost rejection reason flag
        if (MagicAttributes::setterExists($entity, 'setBoostRejectionReason')) {
            $entity->setBoostRejectionReason($reason);
            $dirty = true;
        } elseif (property_exists($entity, 'boost_rejection_reason')) {
            $entity->boost_rejection_reason = true;
            $dirty = true;
        }

        return $dirty;
    }

    /**
     * Get a user's own submitted Boosts
     * @param string $guid
     * @param  int $limit
     * @param  string $offset
     * @return array
     */
    public function getOutbox($guid, $limit, $offset = "")
    {
        /** @var Core\Boost\Repository $repository */
        $repository = Core\Di\Di::_()->get('Boost\Repository');
        $boosts = $repository->getAll($this->type, [
            'owner_guid' => $guid,
            'limit' => $limit,
            'offset' => $offset,
            'order' => 'DESC'
        ]);

        return $boosts;
    }

    /**
     * Return boosts for review
     * @param  int $limit
     * @param  string $offset
     * @return array
     */
    public function getReviewQueue($limit, $offset = "")
    {
        return $this->manager->getList([
            'type' => $this->type,
            'state' => 'review',
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    /**
     * Return the review count
     * @return int
     */
    public function getReviewQueueCount()
    {
        $this->analytics->setType($this->type);
        return $this->analytics->getReviewCount();
    }
}
