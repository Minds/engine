<?php
declare(strict_types=1);

namespace Minds\Core\Monetization\Partners\Delegates;

use Exception;
use Minds\Common\SystemUser;
use Minds\Core\Di\Di;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Monetization\Partners\EarningsDeposit;
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
     * @param EarningsDeposit $deposit
     * @return void
     * @throws Exception
     */
    public function onIssueAffiliateDeposit(User $affiliate, EarningsDeposit $deposit) : void
    {
        $event = (new ActionEvent())
            ->setUser(new SystemUser)
            ->setEntity($affiliate)
            ->setAction(ActionEvent::ACTION_AFFILIATE_EARNINGS_DEPOSITED)
            ->setActionData($deposit->export())
            ->setTimestamp(time());

        $this->actionEventsTopic->send($event);
    }

    /**
     * OnAdd, submit the ActionEvent
     * @param User $referrer
     * @param EarningsDeposit $deposit
     * @return void
     * @throws Exception
     */
    public function onIssueAffiliateReferrerDeposit(User $referrer, EarningsDeposit $deposit) : void
    {
        $event = (new ActionEvent())
            ->setUser(new SystemUser)
            ->setEntity($referrer)
            ->setAction(ActionEvent::ACTION_REFERRER_AFFILIATE_EARNINGS_DEPOSITED)
            ->setActionData($deposit->export())
            ->setTimestamp(time());

        $this->actionEventsTopic->send($event);
    }
}
