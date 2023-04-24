<?php
declare(strict_types=1);

namespace Minds\Core\Monetization\Partners\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Entities\User;

class DepositsDelegate
{
    public function __construct(
        private ?ActionEventsTopic $actionEventsTopic = null
    ) {
        $this->actionEventsTopic ??= Di::_()->get('EventStreams\Topics\ActionEventsTopic');
    }

    /**
     * OnAdd, submit the ActionEvent
     * @param User $affiliate
     * @return void
     */
    public function onIssueAffiliateDeposit(User $affiliate) : void
    {
        $event = (new ActionEvent())
            ->setUser($affiliate)
            ->setAction(ActionEvent::ACTION_AFFILIATE_EARNINGS_DEPOSITED)
            ->setTimestamp(time());

        $this->actionEventsTopic->send($event);
    }

    /**
     * OnAdd, submit the ActionEvent
     * @param User $referrer
     * @return void
     */
    public function onIssueAffiliateReferrerDeposit(User $referrer) : void
    {
        $event = (new ActionEvent())
            ->setUser($referrer)
            ->setAction(ActionEvent::ACTION_REFERRER_AFFILIATE_EARNINGS_DEPOSITED)
            ->setTimestamp(time());

        $this->actionEventsTopic->send($event);
    }
}
