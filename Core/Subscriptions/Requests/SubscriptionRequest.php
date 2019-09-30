<?php
/**
 * SubscriptionRequest Model
 */
namespace Minds\Core\Subscriptions\Requests;

use Minds\Entities\User;
use Minds\Traits\MagicAttributes;

/**
 * @method SubscriptionRequest setPublisherGuid(string $publisherGuid)
 * @method string getPublisherGuid()
 * @method SubscriptionRequest setPublisher(User $user)
 * @method User getPublisher()
 * @method SubscriptionRequest setSubscriberGuid(string $subscriberGuid)
 * @method string getSubscriberGuid()
 * @method SubscriptionRequest setSubscriber(User $subscriber)
 * @method User getSubscriber()
 * @method SubscriptionRequest setDeclined(bool $declined)
 * @method bool getDeclined()
 * @method SubscriptionRequest setTimestampMs(int $timestampMs)
 * @method int getTimestampMs()
 */
class SubscriptionRequest
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

    /** @var bool */
    private $declined = false;

    /** @var int */
    private $timestampMs;

    /**
     * @return string
     */
    public function getUrn(): string
    {
        return "urn:subscription-request:" . implode('-', [ $this->publisherGuid, $this->subscriberGuid ]);
    }

    /**
     * Export
     * @return array
     */
    public function export(): array
    {
        return [
            'publisher_guid' => (string) $this->publisherGuid,
            'publisher' => $this->publisher ? $this->publisher->export() : null,
            'subscriber_guid' => (string) $this->subscriberGuid,
            'subscriber' => $this->subscriber ? $this->subscriber->export() : null,
            'declined' => (bool) $this->declined,
            'timestamp_ms' => $this->timestampMs,
            'timestamp_sec' => round($this->timestampMs / 1000),
        ];
    }
}
