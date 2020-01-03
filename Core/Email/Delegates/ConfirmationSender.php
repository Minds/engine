<?php
/**
 * ConfirmationSender
 *
 * @author edgebal
 */

namespace Minds\Core\Email\Delegates;

use Minds\Core\Email\Campaigns\Confirmation;
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
