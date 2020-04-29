<?php

namespace Minds\Core\Email\V2\Delegates;

use Minds\Core\Suggestions\Manager;
use Minds\Entities\User;
use Minds\Core\Di\Di;
use Minds\Interfaces\SenderInterface;
use Minds\Core\Email\V2\Campaigns\Recurring\WeMissYou\WeMissYou;

class WeMissYouSender implements SenderInterface
{
    /** @var Manager */
    private $manager;
    /** @var WeMissYou */
    private $campaign;

    public function __construct(Manager $manager = null, WeMissYou $campaign = null)
    {
        $this->manager = $manager ?: Di::_()->get('Suggestions\Manager');
        $this->campaign = $campaign ?: new WeMissYou();
    }

    public function send(User $user)
    {
        $this->manager->setUser($user);
        $suggestions = $this->manager->getList();
        $this->campaign->setUser($user);
        $this->campaign->setSuggestions($suggestions);
        $this->campaign->send();
    }
}
