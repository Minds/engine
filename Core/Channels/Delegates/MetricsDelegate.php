<?php
/**
 * @author: eiennohi.
 */

namespace Minds\Core\Channels\Delegates;

use Minds\Core\Analytics\Metrics\Event;
use Minds\Entities\User;

class MetricsDelegate
{
    public function onDelete(User $user)
    {
        $event = new Event();
        $event->setType('action')
            ->setAction('delete')
            ->setProduct('platform')
            ->setUserGuid((string) $user->guid)
            ->setUserPhoneNumberHash($user->getPhoneNumberHash())
            ->push();
    }
}
