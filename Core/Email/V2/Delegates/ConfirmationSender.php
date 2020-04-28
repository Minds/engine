<?php
/**
 * ConfirmationSender
 *
 * @author edgebal
 */

namespace Minds\Core\Email\V2\Delegates;

use Minds\Core\Email\V2\Campaigns\Recurring\Confirmation\Confirmation;
use Minds\Entities\User;
use Minds\Interfaces\SenderInterface;

class ConfirmationSender implements SenderInterface
{
    /**
     * @param User $user
     */
    public function send(User $user)
    {
        (new Confirmation())
            ->setUser($user)
            ->send();
    }
}
