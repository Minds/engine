<?php
/**
 * Subscriptions manager
 */
namespace Minds\Core\Subscriptions;

use Minds\Core\Subscriptions\Delegates\CacheDelegate;
use Minds\Core\Subscriptions\Delegates\CopyToElasticSearchDelegate;
use Minds\Core\Subscriptions\Delegates\EventsDelegate;
use Minds\Core\Subscriptions\Delegates\FeedsDelegate;
use Minds\Core\Subscriptions\Delegates\SendNotificationDelegate;
use Minds\Core\Suggestions\Delegates\CheckRateLimit;
use Minds\Entities\User;

class Manager
{
    const MAX_SUBSCRIPTIONS = 5000;

    /** @var Repository $repository */
    private $repository;

    /** @var User $subscriber */
    private $subscriber;

    /** @var CopyToElasticSearchDelegate $copyToElasticSearchDelegate */
    private $copyToElasticSearchDelegate;

    /** @var SendNotificationDelegate $sendNotificationDelegate */
    private $sendNotificationDelegate;

    /** @var CacheDelegate $cacheDelegate */
    private $cacheDelegate;

    /** @var EventsDelegate $eventsDelegate */
    private $eventsDelegate;

    /** @var FeedsDelegate $feedsDelegate */
    private $feedsDelegate;

    /** @var CheckRateLimit */
    private $checkRateLimitDelegate;

    /** @var bool */
    private $sendEvents = true;

    public function __construct(
        $repository = null,
        $copyToElasticSearchDelegate = null,
        $sendNotificationDelegate = null,
        $cacheDelegate = null,
        $eventsDelegate = null,
        $feedsDelegate = null,
        $checkRateLimitDelegate = null
    ) {
        $this->repository = $repository ?: new Repository;
        $this->copyToElasticSearchDelegate = $copyToElasticSearchDelegate ?: new Delegates\CopyToElasticSearchDelegate;
        $this->sendNotificationDelegate = $sendNotificationDelegate ?: new Delegates\SendNotificationDelegate;
        $this->cacheDelegate = $cacheDelegate ?: new Delegates\CacheDelegate;
        $this->eventsDelegate = $eventsDelegate ?: new Delegates\EventsDelegate;
        $this->feedsDelegate = $feedsDelegate ?: new Delegates\FeedsDelegate;
        $this->checkRateLimitDelegate = $checkRateLimitDelegate ?: new CheckRateLimit();
    }

    /**
     * Gets a subscription or subscribers list from the repository.
     *
     * @param array $opts -
     *  guid - required!
     *  type - either 'subscribers' or 'subscriptions'.
     *  limit - limit.
     *  offset - offset.
     * @return Response response objet
     */
    public function getList($opts)
    {
        if (!$opts['guid']) {
            return [];
        }
        
        $opts = array_merge([
            'limit' => 12,
            'offset' => '',
            'guid' => '',
            'type' => 'subscribers',
        ], $opts);
        
        return $this->repository->getList($opts);
    }

    public function setSubscriber($user)
    {
        $this->subscriber = $user;
        return $this;
    }

    /**
     * @param bool $sendEvents
     * @return Manager
     */
    public function setSendEvents($sendEvents)
    {
        $this->sendEvents = $sendEvents;
        return $this;
    }

    /**
     * NOT IMPLEMENTED.. USING LEGACY CODE!
     * Is the subscriber subscribed to the publisher
     * @param User $publisher
     * @return bool
     */
    public function isSubscribed($publisher)
    {
        $subscription = new Subscription();
        $subscription->setSubscriberGuid($this->subscriber->getGuid())
            ->setPublisherGuid($publisher->getGuid());
        return $this->repository->get($subscription);
    }

    /**
     * Subscribe to a publisher
     * @param User $publisher
     * @return Subscription
     */
    public function subscribe($publisher)
    {
        $subscription = new Subscription();
        $subscription->setSubscriberGuid($this->subscriber->getGuid())
            ->setPublisherGuid($publisher->getGuid());

        if ($this->getSubscriptionsCount() >= static::MAX_SUBSCRIPTIONS && $this->sendEvents) {
            $this->sendNotificationDelegate->onMaxSubscriptions($subscription);
            throw new TooManySubscriptionsException();
        }

        $subscription = $this->repository->add($subscription);

        $this->eventsDelegate->trigger($subscription);
        //$this->feedsDelegate->copy($subscription);
        $this->copyToElasticSearchDelegate->copy($subscription);
        $this->cacheDelegate->cache($subscription);
        $this->checkRateLimitDelegate->incrementCache($this->subscriber->guid);

        if ($this->sendEvents) {
            $this->sendNotificationDelegate->send($subscription);
        }

        return $subscription;
    }

    /**
     * UnSubscribe to a publisher
     * @param User $publisher
     * @return Subscription
     */
    public function unSubscribe($publisher)
    {
        $subscription = new Subscription();
        $subscription->setSubscriberGuid($this->subscriber->getGuid())
            ->setPublisherGuid($publisher->getGuid())
            ->setActive(false);

        $subscription = $this->repository->delete($subscription);

        $this->eventsDelegate->trigger($subscription);
        $this->feedsDelegate->remove($subscription);
        $this->copyToElasticSearchDelegate->remove($subscription);
        $this->cacheDelegate->cache($subscription);

        return $subscription;
    }

    /**
     * Return the count of subscriptions a user has
     * @return int
     */
    public function getSubscriptionsCount()
    {
        return $this->subscriber->getSubscriptionsCount(); //TODO: Refactor so we are the source of truth
    }
}
