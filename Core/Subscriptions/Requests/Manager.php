<?php
/**
 * Subscriptions Requests Manager
 */
namespace Minds\Core\Subscriptions\Requests;

use Minds\Core\Subscriptions\Requests\Delegates\NotificationsDelegate;
use Minds\Core\Subscriptions\Requests\Delegates\SubscriptionsDelegate;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Di\Di;
use Minds\Common\Repository\Response;
use Minds\Entities\User;

class Manager
{
    /** @var Repository */
    private $repository;

    /** @var NotificationsDelegate */
    private $notificationsDelegate;

    /** @var SubscriptionsDelegate */
    private $subscriptionsDelegate;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    public function __construct($repository = null, $notificationsDelegate = null, $subscriptionsDelegate = null, $entitiesBuilder = null)
    {
        $this->repository = $repository ?? new Repository();
        $this->notificationsDelegate = $notificationsDelegate ?? new NotificationsDelegate;
        $this->subscriptionsDelegate = $subscriptionsDelegate ?? new SubscriptionsDelegate;
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * Return a list of incoming subscription requests
     * @param string $userGuid
     * @param array $opts
     * @return Response
     */
    public function getIncomingList(string $userGuid, array $opts = []): Response
    {
        $opts = array_merge([
            'hydrate' => true,
        ], $opts);

        $opts['publisher_guid'] = $userGuid;
        $response = $this->repository->getList($opts);

        if ($opts['hydrate']) {
            foreach ($response as $i => $request) {
                $request->setSubscriber($this->entitiesBuilder->single($request->getSubscriberGuid()));
            }
        }

        return $response;
    }

    /**
     * Return a subscription request
     * @param string $urn
     * @return SubscriptionRequest
     */
    public function get(string $urn): ?SubscriptionRequest
    {
        return $this->repository->get($urn);
    }

    /**
     * Add a subscription request
     * @param SubscriptionRequest $subscriptionRequest
     * @return bool
     */
    public function add(SubscriptionRequest $subscriptionRequest): bool
    {
        // Check if exists
        $existing = $this->get($subscriptionRequest->getUrn());
        if ($existing) {
            throw new SubscriptionRequestExistsException();
        }

        // Check if the user exists
        $publisher = $this->entitiesBuilder->single($subscriptionRequest->getPublisherGuid());
        if (!$publisher || !$publisher instanceof User) {
            throw new SubscriptionRequestChannelDoesntExist();
        }

        $this->repository->add($subscriptionRequest);

        $this->notificationsDelegate->onAdd($subscriptionRequest);

        return true;
    }

    /**
     * Accept a subscription request
     * @param SubscriptionRequest $subscriptionRequest
     * @return bool
     */
    public function accept(SubscriptionRequest $subscriptionRequest): bool
    {
        // Check if exists
        $existing = $this->get($subscriptionRequest->getUrn());
        if (!$existing) {
            throw new SubscriptionRequestDoesntExistException();
        }

        if ($existing->isDeclined()) {
            throw new SubscriptionRequestAlreadyCompletedException();
        }

        $this->repository->delete($subscriptionRequest);

        $this->notificationsDelegate->onAccept($subscriptionRequest);
        $this->subscriptionsDelegate->onAccept($subscriptionRequest);

        return true;
    }

    /**
     * Decline a subscription request
     * @param SubscriptionRequest $subscriptionRequest
     * @return bool
     */
    public function decline(SubscriptionRequest $subscriptionRequest): bool
    {
        // Check if exists
        $existing = $this->get($subscriptionRequest->getUrn());
        if (!$existing) {
            throw new SubscriptionRequestDoesntExistException();
        }

        if ($existing->isDeclined()) {
            throw new SubscriptionRequestAlreadyCompletedException();
        }

        $subscriptionRequest->setDeclined(true);
        $this->repository->update($subscriptionRequest);

        $this->notificationsDelegate->onDecline($subscriptionRequest);

        return true;
    }
}
