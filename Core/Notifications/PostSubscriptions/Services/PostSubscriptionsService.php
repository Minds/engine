<?php
namespace Minds\Core\Notifications\PostSubscriptions\Services;

use Minds\Core\Notifications\PostSubscriptions\Enums\PostSubscriptionFrequencyEnum;
use Minds\Core\Notifications\PostSubscriptions\Models\PostSubscription;
use Minds\Core\Notifications\PostSubscriptions\Repositories\PostSubscriptionsRepository;
use Minds\Entities\EntityInterface;
use Minds\Entities\User;

class PostSubscriptionsService
{
    private User $user;
    private EntityInterface $entity;

    public function __construct(
        private PostSubscriptionsRepository $repository,
    ) {
        
    }

    /**
     * Set the user (usually logged in user)
     */
    public function withUser(User $user): PostSubscriptionsService
    {
        $instance = clone $this;
        $instance->user = $user;
        return $instance;
    }

    /**
     * Sets the entity
     */
    public function withEntity(EntityInterface $entity): PostSubscriptionsService
    {
        $instance = clone $this;
        $instance->entity = $entity;
        return $instance;
    }

    /**
     * Subscribes a user to notifications from the entity
     */
    public function subscribe(PostSubscriptionFrequencyEnum $frequency): bool
    {
        $postSubscription = new PostSubscription(
            (int) $this->user->getGuid(),
            (int) $this->entity->getGuid(),
            $frequency,
        );

        return $this->repository->upsert($postSubscription);
    }

    
    /**
     * Checks if a subscription exists, and if the frequency is anything BUT never
     */
    public function isSubscribed(): bool
    {
        $postSubscription = $this->get();

        return $postSubscription->frequency !== PostSubscriptionFrequencyEnum::NEVER;
    }

    /**
     * Returns a subscriptions. If one isn't found, we make a new one with NEVER as the frequency
     */
    public function get(): PostSubscription
    {
        return $this->repository->get(
            userGuid: (int) $this->user->getGuid(),
            entityGuid: (int) $this->entity->getGuid(),
        ) ?: new PostSubscription(
            userGuid: (int) $this->user->getGuid(),
            entityGuid: (int) $this->entity->getGuid(),
            frequency: PostSubscriptionFrequencyEnum::NEVER,
        );
    }

}
