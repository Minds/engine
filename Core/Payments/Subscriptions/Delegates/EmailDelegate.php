<?php
namespace Minds\Core\Payments\Subscriptions\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Payments\Subscriptions\Subscription;
use Minds\Core\Plus;
use Minds\Core\Email\V2\Campaigns\Recurring\OnPlusTrial\OnPlusTrial;

class EmailDelegate
{
    /** @var OnPlusTrial */
    private $onPlusTrialEmail;

    /** @var Plus\Manager */
    private $plusManager;

    public function __construct($onPlusTrialEmail = null, $plusManager = null)
    {
        $this->onPlusTrialEmail = $onPlusTrialEmail ?? new OnPlusTrial();
        $this->plusManager = $plusManager ?? Di::_()->get('Plus\Manager');
    }

    /**
     * @var Subscription $subscription
     * @return void
     */
    public function onCharge(Subscription $subscription): void
    {
    }

    /**
     * @var Subscription $subscription
     * @return void
     */
    public function onCreate(Subscription $subscription): void
    {
        if ($subscription->getEntity()->getGuid() == $this->plusManager->getPlusGuid() && $subscription->getTrialDays() > 0) {
            $this->onPlusTrialEmail
                ->setSubscription($subscription)
                ->setUser($subscription->getUser())
                ->send();
        }
    }

    /**
     * @var Subscription $subscription
     * @return void
     */
    public function onCancel(Subscription $subscription): void
    {
    }
}
