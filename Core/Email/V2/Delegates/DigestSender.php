<?php

namespace Minds\Core\Email\V2\Delegates;

use Minds\Entities\User;
use Minds\Core\Di\Di;
use Minds\Interfaces\SenderInterface;
use Minds\Core\Email\V2\Campaigns\Recurring\Digest\Digest;

class DigestSender implements SenderInterface
{
    /** @var Digest */
    private $campaign;

    public function __construct(Digest $campaign = null)
    {
        $this->campaign = $campaign ?: new Digest();
    }

    public function send(User $user)
    {
        $this->campaign->setUser($user);
        $this->campaign->send();
    }
}
