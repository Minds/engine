<?php

namespace Minds\Core\Payments\Stripe\Connect\Delegates;

use Minds\Core\Payments\Stripe\Connect\Account;
use Minds\Core\Di\Di;
use Minds\Core\Events\EventsDispatcher;

class NotificationDelegate
{
    /** @var EventsDispatcher */
    private $eventsDispatcher;

    public function __construct($eventsDispatcher = null)
    {
        $this->eventsDispatcher = $eventsDispatcher ?: Di::_()->get('EventsDispatcher');
    }

    /**
     * @param Account $account
     * @return void
     */
    public function onAccepted(Account $account)
    {
        $this->eventsDispatcher->trigger('notification', 'program', [
            'to'=> [ $account->getUserGuid() ],
            'from' => 100000000000000519,
            'notification_view' => 'program_accepted',
            'params' => [ 'program' => 'monetization' ],
        ]);
    }
}
