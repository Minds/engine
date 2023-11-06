<?php
/**
 * Subscriptions manager
 */
namespace Minds\Core\Subscriptions;

use Minds\Core\Config\Config;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Di\Di;
use Minds\Core\Subscriptions\Delegates\CacheDelegate;
use Minds\Core\Subscriptions\Delegates\CopyToElasticSearchDelegate;
use Minds\Core\Subscriptions\Delegates\EventsDelegate;
use Minds\Core\Subscriptions\Delegates\FeedsDelegate;
use Minds\Core\Subscriptions\Delegates\SendNotificationDelegate;
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

    /** @var bool */
    private $sendEvents = true;

    public function __construct(
        $repository = null,
        $copyToElasticSearchDelegate = null,
        $sendNotificationDelegate = null,
        $cacheDelegate = null,
        $eventsDelegate = null,
        $feedsDelegate = null,
        protected ?Relational\Repository $relationalRepository = null,
        protected ?PsrWrapper $cache = null,
        protected ?Config $config = null,
    ) {
        $this->repository = $repository ?: new Repository;
        $this->copyToElasticSearchDelegate = $copyToElasticSearchDelegate ?: new Delegates\CopyToElasticSearchDelegate;
        $this->sendNotificationDelegate = $sendNotificationDelegate ?: new Delegates\SendNotificationDelegate;
        $this->cacheDelegate = $cacheDelegate ?: new Delegates\CacheDelegate;
        $this->eventsDelegate = $eventsDelegate ?: new Delegates\EventsDelegate;
        $this->feedsDelegate = $feedsDelegate ?: new Delegates\FeedsDelegate;
        $this->relationalRepository ??= new Relational\Repository();
        $this->cache ??= Di::_()->get('Cache\PsrWrapper');
        $this->config ??= Di::_()->get(Config::class);
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

        if ($this->isMultiTenant()) {
            return $this->relationalRepository->isSubscribed(
                userGuid: $this->subscriber->getGuid(),
                friendGuid: $publisher->getGuid(),
            );
        }

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
        $subscription->setSubscriber($this->subscriber)
            ->setPublisher($publisher);

        if ($this->getSubscriptionsCount() >= static::MAX_SUBSCRIPTIONS && $this->sendEvents) {
            $this->sendNotificationDelegate->onMaxSubscriptions($subscription);
            throw new TooManySubscriptionsException();
        }

        if (!$this->isMultiTenant()) {
            $subscription = $this->repository->add($subscription);
        }

        $this->eventsDelegate->trigger($subscription);

        $this->copyToElasticSearchDelegate->copy($subscription);
        $this->cacheDelegate->cache($subscription);

        // Copy this over to relational too
        $this->relationalRepository->add($subscription);

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
        $subscription->setSubscriber($this->subscriber)
            ->setPublisher($publisher)
            ->setActive(false);

        if (!$this->isMultiTenant()) {
            $subscription = $this->repository->delete($subscription);
        }

        $this->eventsDelegate->trigger($subscription);
        $this->feedsDelegate->remove($subscription);
        $this->copyToElasticSearchDelegate->remove($subscription);
        $this->cacheDelegate->cache($subscription);

        // Copy this over to relational too
        $this->relationalRepository->delete($subscription);

        return $subscription;
    }

    /**
     * Return the count of subscriptions a user has
     * @return int
     */
    public function getSubscriptionsCount()
    {
        $userGuid = $this->subscriber->getGuid();
        if ($cache = $this->cache->get("$userGuid:friendscount")) {
            //return $cache;
        }

        $repository = $this->isMultiTenant() ? $this->relationalRepository : $this->repository;

        $count = $repository->getSubscriptionsCount($userGuid);
   
        $this->cache->set("$userGuid:friendscount", $count, 259200); //cache for 3 days

        return $count;
    }

    /**
     * Return the count of subscribers a user has
     * @return int
     */
    public function getSubscribersCount(): int
    {
        $userGuid = $this->subscriber->getGuid();
        if ($cache = $this->cache->get("$userGuid:friendsofcount")) {
            return $cache;
        }
    
        $repository = $this->isMultiTenant() ? $this->relationalRepository : $this->repository;

        $count = $repository->getSubscribersCount($userGuid);

        $this->cache->set("$userGuid:friendsofcount", $count, 259200); //cache for 3 days

        return $count;
    }

    private function isMultiTenant(): bool
    {
        return !!$this->config->get('tenant_id');
    }
}
