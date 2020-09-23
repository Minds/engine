<?php
namespace Minds\Core\Monetization\EarningsOverview;

use Minds\Entities\User;
use Minds\Core\Di\Di;
use Minds\Core\Payments\Stripe;
use Minds\Core\Monetization\Partners;

class Manager
{
    /** @var User */
    protected $user;

    /** @var Stripe\Connect\Manager */
    protected $stripeConnectManager;

    /** @var Stripe\Transactions\Manager */
    protected $stripeTransactionsManager;

    /** @var Partners\Manager */
    protected $partnerEarningsManager;

    public function __construct(
        $stripeConnectManager = null,
        $stripeTransactionsManager = null,
        $partnerEarningsManager = null,
        $config = null
    ) {
        $this->stripeConnectManager = $stripeConnectManager ?? Di::_()->get('Stripe\Connect\Manager');
        $this->stripeTransactionsManager = $stripeTransactionsManager ?? Di::_()->get('Stripe\Transactions\Manager');
        $this->partnerEarningsManager = $partnerEarningsManager ?? Di::_()->get('Monetization\Partners\Manager');
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * @param User $user
     * @return Manager
     */
    public function setUser(User $user): Manager
    {
        $manager = clone $this;
        $manager->user = $user;
        return $manager;
    }

    /**
     * Return the overview
     * @return OverviewModel
     */
    public function getOverview(int $from, int $to): OverviewModel
    {
        $overview = new OverviewModel();
        
        $overview->setEarnings([
            $this->getPartnerEarnings($from, $to),
            $this->getWireEarnings($from, $to),
        ]);

        $overview->setPayouts($this->getPayouts($from, $to));

        return $overview;
    }

    /**
     * @param int $from
     * @param int $to
     * @return array
     */
    protected function getPayouts($from, $to): array
    {
        $partnerEarnings = $this->partnerEarningsManager->getList([
            'user_guid' => $this->user->getGuid(),
            'from' => $from,
            'to' => $to,
        ]);
        $partnerEarningsItems = $this->buildPartnerEarningsItemModels($partnerEarnings);

        if (!isset($partnerEarningsItems['payout'])) {
            return [];
        }

        $partnerEarningsItems['payout']->setId('partner');
        $partnerEarningsItems['payout']->setAmountCents($partnerEarningsItems['payout']->getAmountCents() * -1);

        return [ $partnerEarningsItems['payout'] ];
    }

    /**
     * @param int $from
     * @param int $to
     * @return EarningsGroupModel
     */
    protected function getPartnerEarnings($from, $to): EarningsGroupModel
    {
        $partnerEarnings = $this->partnerEarningsManager->getList([
            'user_guid' => $this->user->getGuid(),
            'from' => $from,
            'to' => $to,
        ]);
        $partnerEarningsItems = $this->buildPartnerEarningsItemModels($partnerEarnings);

        $earnings = new EarningsGroupModel();
        $earnings->setId('partner');

        // Pageviews
        $pageViewEarnings = $partnerEarningsItems['views'] ?? new EarningsItemModel();
        $pageViewEarnings->setId('pageviews');

        // Referrals
        $referralEarnings = $partnerEarningsItems['referrals'] ?? new EarningsItemModel();
        $referralEarnings->setId('referrals');

        // Plus earnings
        $plusEarnings = $partnerEarningsItems['plus'] ?? new EarningsItemModel();
        $plusEarnings->setId('plus');

        // Wire referrals
        $wireReferrals = $partnerEarningsItems['wire_referral'] ?? new EarningsItemModel();
        $wireReferrals->setId('wire_referral');
        
        $earnings->setItems([
            $pageViewEarnings,
            $referralEarnings,
            $plusEarnings,
            $wireReferrals,
        ]);

        return $earnings;
    }

    /**
     * @param int $from
     * @param int $to
     * @return EarningsGroupModel
     */
    protected function getWireEarnings($from, $to): EarningsGroupModel
    {
        $stripeAccount = $this->stripeConnectManager->getByUser($this->user);

        if (!$stripeAccount) {
            return new EarningsGroupModel();
        }

        $wireTransfers = $this->stripeTransactionsManager
            ->setAccount($stripeAccount)
            ->getTransfers([
                'from' => $from,
                'to' => $to,
            ]);

        $sum = 0;
        $currency = 'usd';
        foreach ($wireTransfers as $transfer) {
            if ($transfer->getType() === 'wire'
                //&& $transfer->getCustomerUserGuid() != $this->config->get('pro')['handler']
            ) {
                $sum += $transfer->getNet();
                $currency = $transfer->getCurrency();
            }
        }

        $earningsGroupModel =  new EarningsGroupModel();
        $earningsGroupModel->setId('wire');

        $wireEarnings = new EarningsItemModel();
        $wireEarnings->setId('wire-all');
        $wireEarnings->setAmountCents($sum);
        $wireEarnings->setCurrency($currency);

        $earningsGroupModel->setItems([ $wireEarnings ]);
        $earningsGroupModel->setCurrency($currency);
        return $earningsGroupModel;
    }

    /**
     * @param array $earningsDeposits
     * @return EarningsGroupModel[]
     */
    protected function buildPartnerEarningsItemModels($earningsDeposits = []): array
    {
        $groups = [];

        foreach ($earningsDeposits as $earningsDeposit) {
            $earningsDepositItem = $groups[$earningsDeposit->getItem()] ?? new EarningsItemModel();
            $earningsDepositItem->setAmountCents($earningsDepositItem->getAmountCents() + $earningsDeposit->getAmountCents());
            $earningsDepositItem->setCurrency('usd');
            $groups[$earningsDeposit->getItem()] = $earningsDepositItem;
        }

        return $groups;
    }
}
