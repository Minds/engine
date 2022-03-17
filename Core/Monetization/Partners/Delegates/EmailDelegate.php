<?php
namespace Minds\Core\Monetization\Partners\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Config;
use Minds\Core\Email\V2\Campaigns\Custom\Custom;
use  Minds\Core\Monetization\Partners\EarningsPayout;

class EmailDelegate
{
    /** @var Config */
    private $config;

    /** @var Custom */
    protected $campaign;

    public function __construct($config = null, $campaign = null)
    {
        $this->config = $config ?: Di::_()->get('Config');
        $this->campaign = $campaign ?: new Custom;
    }

    /**
     * Sends an email
     * @param EarningsPayout $earningsPayout
     * @return void
     */
    public function onIssuePayout(EarningsPayout $earningsPayout): void
    {
        if ($earningsPayout->getMethod() === 'usd' && !$earningsPayout->getUser()->getMerchant()['id']) {
            $this->sendPendingPayout($earningsPayout);
        } else {
            $this->sendCompletedPayout($earningsPayout);
        }
    }

    private function sendCompletedPayout(EarningsPayout $earningsPayout): void
    {
        echo "\n " . dirname(__FILE__) . '/emails/completed.md';
        $this->campaign
            ->setUser($earningsPayout->getUser())
            ->setSubject("Your payout is on its way")
            ->setTemplate(dirname(__FILE__) . '/emails/completed.md')
            ->setTopic('billing')
            ->setCampaign('pro')
            ->setVars([
                'usd' => number_format($earningsPayout->getAmountCents() / 100, 2),
                'method' => $earningsPayout->getMethod(),
            ])
            ->send();
    }

    private function sendPendingPayout(EarningsPayout $earningsPayout): void
    {
        $this->campaign
            ->setUser($earningsPayout->getUser())
            ->setSubject("Action required to receive your payout")
            ->setTemplate(dirname(__FILE__) . '/emails/pending.md')
            ->setTopic('billing')
            ->setCampaign('pro')
            ->setVars([
                'usd' => number_format($earningsPayout->getAmountCents() / 100, 2),
            ])
            ->send();
    }
}
