<?php
/**
 * WelcomeSender
 *
 * @author mark
 */

namespace Minds\Core\Email\V2\Delegates;

use Minds\Core\Email\V2\Campaigns\Recurring\Welcome\Welcome;
use Minds\Entities\User;
use Minds\Interfaces\SenderInterface;

class WelcomeSender implements SenderInterface
{
    /**
     * @param User $user
     */
    public function send(User $user)
    {
        (new Welcome())
            ->setUser($user)
            ->send();
    }
}
