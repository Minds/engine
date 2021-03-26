<?php
namespace Minds\Core\Security\TOTP\Delegates;

use Minds\Core\Email\V2\Campaigns\Custom\Custom;
use Minds\Entities\User;

class EmailDelegate
{
    /** @var Custom */
    protected $campaign;

    public function __construct($campaign = null)
    {
        $this->campaign = $campaign ?: new Custom;
    }

    /**
     * Recovery code verified
     * @param User $user
     * @return void
     */
    public function onRecover(User $user)
    {
        $subject = '2FA disabled';

        $this->campaign->setUser($user);
        $this->campaign->setTemplate('totp-recovery-code-used');
        $this->campaign->setSubject($subject);
        $this->campaign->setTitle($subject);
        $this->campaign->setPreheader('Two-factor security has been disabled. ');

        $this->campaign->send();
    }
}
