<?php
namespace Minds\Core\Payments\Subscriptions\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Payments\Subscriptions\Subscription;
use Minds\Core\Analytics\Snowplow\Events\SnowplowWireSubscriptionEvent;

class SnowplowDelegate
{
    /** @var Snowplow\Manager */
    private $snowplowManager;

    public function __construct($snowplowManager = null)
    {
        $this->snowplowManager = $snowplowManager ?? Di::_()->get('Analytics\Snowplow\Manager');
    }

    /**
     * @var Subscription $subscription
     * @return void
     */
    public function onCharge(Subscription $subscription): void
    {
        $this->emit($subscription);
    }

    /**
     * @var Subscription $subscription
     * @return void
     */
    public function onCreate(Subscription $subscription): void
    {
        $this->emit($subscription);
    }

    /**
     * @var Subscription $subscription
     * @return void
     */
    public function onCancel(Subscription $subscription): void
    {
        $this->emit($subscription);
    }

    /**
     * @var Subscription $subscription
     * @return void
     */
    private function emit(Subscription $subscription): void
    {
        $spEvent = new SnowplowWireSubscriptionEvent();
        $spEvent->setSubscription($subscription);

        $this->snowplowManager->setSubject(null)->emit($spEvent);
    }
}
