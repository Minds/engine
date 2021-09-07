<?php
/**
 * Subscription Model
 */
namespace Minds\Core\Subscriptions;

use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

/**
 * @method Subscription isActive(): boolean
 * @method Subscription getSubscriberGuid(): string
 * @method Subscription getPublisherGuid(): string
 * @method Subscription setSubscriberGuid(string $guid): self
 * @method Subscription setPublisherGuid(string $guid): self
 * @method User getSubscriber()
 * @method User getPublisher()
 */
class Subscription
{
    use MagicAttributes;

    /** @var string */
    private $publisherGuid;

    /** @var User */
    private $publisher;

    /** @var string */
    private $subscriberGuid;

    /** @var User */
    private $subscriber;

    /** @var bool $active */
    private $active = false;

    /**
     * @param User $user
     * @return self
     */
    public function setPublisher(User $user): self
    {
        $this->publisherGuid = $user->getGuid();
        $this->publisher = $user;
        return $this;
    }

    /**
     * @param User $user
     * @return self
     */
    public function setSubscriber(User $user): self
    {
        $this->subscriberGuid = $user->getGuid();
        $this->subscriber = $user;
        return $this;
    }
}
