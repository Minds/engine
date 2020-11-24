<?php
namespace Minds\Core\Analytics\Snowplow\Events;

use Minds\Traits\MagicAttributes;
use Minds\Core\Analytics\Snowplow\Contexts\SnowplowContextInterface;
use Minds\Core\Payments\Subscriptions\Subscription;
use Minds\Core\Util\BigNumber;

/**
 * @method SnowplowActionEvent setSubscription(Subscription $subscription)
 */
class SnowplowWireSubscriptionEvent implements SnowplowEventInterface
{
    use MagicAttributes;

    /** @var Subscription */
    protected $subscription;

    /** @var SnowplowContextInterface[] */
    protected $context = [];

    /**
     * Returns the schema
     */
    public function getSchema(): string
    {
        return "iglu:com.minds/wire_subscription/jsonschema/1-0-0";
    }

    /**
     * Returns the sanitized data
     * null values are removed
     * @return array
     */
    public function getData(): array
    {
        if ($this->subscription->getPaymentMethod() === 'tokens') {
            $amount = BigNumber::fromPlain($this->subscription->getAmount(), '18')->toDouble();
        } else {
            $amount = $this->subscription->getAmount();
        }

        return array_filter([
            'plan_id' => $this->subscription->getPlanId(),
            'payment_method' => $this->subscription->getPaymentMethod(),
            'entity_guid' => $this->subscription->getEntity() ? (string) $this->subscription->getEntity()->getGuid() : null,
            'user_guid' => $this->subscription->getUser() ? (string) $this->subscription->getUser()->getGuid() : null,
            'id' => $this->subscription->getId(),
            'amount' => $amount,
            'interval' => $this->subscription->getInterval(),
            'last_billing' => date('c', $this->subscription->getLastBilling()),
            'next_billing' => date('c', $this->subscription->getNextBilling()),
            'status' => $this->subscription->getStatus(),
            'is_trial' => $this->subscription->getTrialDays() > 0,
        ]);
    }

    /**
     * Sets the contexts
     * @param SnowplowContextInterface[] $contexts
     */
    public function setContext(array $context = []): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Returns attached contexts
     * @return array
     */
    public function getContext(): ?array
    {
        return array_values($this->context);
    }
}
