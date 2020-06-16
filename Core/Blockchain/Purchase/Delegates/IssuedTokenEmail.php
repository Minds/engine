<?php

/**
 * Minds New Purchase Email Delegate
 *
 * @author mark
 */

namespace Minds\Core\Blockchain\Purchase\Delegates;

use Minds\Core\Blockchain\Purchase\Purchase;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Util\BigNumber;
use Minds\Core\Email\V2\Campaigns\Custom\Custom;
use Minds\Entities\User;

class IssuedTokenEmail
{
    /** @var Config */
    protected $config;

    /** @var Custom */
    protected $campaign;

    public function __construct($config = null, $campaign = null)
    {
        $this->config = $config ?: Di::_()->get('Config');
        $this->campaign = $campaign ?: new Custom;
    }

    public function send(Purchase $purchase)
    {
        $amount = (int) BigNumber::_($purchase->getRequestedAmount())->div(10 ** 18)->toString();


        $subject = 'Thank you for your purchase';

        $this->campaign
            ->setUser(new User($purchase->getUserGuid()))
            ->setSubject($subject)
            ->setTemplate('token-purchase-issued')
            ->setTopic('billing')
            ->setCampaign('tokens')
            ->setTitle($subject)
            ->setSignoff('Thank you,')
            ->setPreheader("Your purchase of $amount Tokens has now been issued.")
            ->setVars([
                'date' => date('l F jS Y', time()),
                'amount' => $amount,
            ])
            ->send();
    }
}
