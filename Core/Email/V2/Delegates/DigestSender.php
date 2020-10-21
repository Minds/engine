<?php

namespace Minds\Core\Email\V2\Delegates;

use Minds\Entities\User;
use Minds\Core\Di\Di;
use Minds\Interfaces\SenderInterface;
use Minds\Core\Email\V2\Campaigns\Recurring\Digest\Digest;
use Minds\Core\Email\V2\Campaigns\Recurring\Digest\PlusDigest;

class DigestSender implements SenderInterface
{
    /** @var Digest */
    private $campaign;

    public function __construct(Digest $campaign = null)
    {
        $this->campaign = $campaign ?: new Digest();
    }

    /**
     * Sets the campaign we are using for the type of digest
     * @param string $variant
     * @return DigestSender
     */
    public function setVariant(string $variant): DigestSender
    {
        $sender = clone $this;

        switch ($variant) {
            case 'plus':
                $sender->campaign = new PlusDigest;
                break;
            default:
                $sender->campaign = new Digest();
        }
        
        return $sender;
    }

    public function send(User $user)
    {
        $this->campaign->setUser($user);
        $this->campaign->send();
    }
}
