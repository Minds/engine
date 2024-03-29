<?php
namespace Minds\Core\Monetization\EarningsOverview;

use Brick\Math\BigDecimal;
use Minds\Common\Repository\Response;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Monetization\Partners;
use Minds\Core\Payments\Stripe;
use Minds\Core\Util\BigNumber;
use Minds\Core\Wire;
use Minds\Entities\User;

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

    /** @var Wire\Sums */
    protected $wireSums;

    /** @var Config */
    protected $config;

    public function __construct(
        $stripeConnectManager = null,
        $stripeTransactionsManager = null,
        $partnerEarningsManager = null,
        $wireSums = null,
        $config = null
    ) {
        $this->stripeConnectManager = $stripeConnectManager ?? Di::_()->get('Stripe\Connect\Manager');
        $this->stripeTransactionsManager = $stripeTransactionsManager ?? Di::_()->get('Stripe\Transactions\Manager');
        $this->partnerEarningsManager = $partnerEarningsManager ?? Di::_()->get('Monetization\Partners\Manager');
        $this->wireSums = $wireSums ?? Di::_()->get('Wire\Sums');
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

        // Boost Partners
        $boostPartners = $partnerEarningsItems['boost_partner'] ?? new EarningsItemModel();
        $boostPartners->setId('boost_partner');

        // Affiliates
        $affiliateEarnings = $partnerEarningsItems['affiliate'] ?? new EarningsItemModel();
        $affiliateEarnings->setId('affiliate');

        // Affiliate referrals
        $affiliateReferrerEarnings = $partnerEarningsItems['affiliate_referrer'] ?? new EarningsItemModel();
        $affiliateReferrerEarnings->setId('affiliate_referrer');

        $earnings->setItems([
            $pageViewEarnings,
            $referralEarnings,
            $plusEarnings,
            $wireReferrals,
            $boostPartners,
            $affiliateEarnings,
            $affiliateReferrerEarnings,
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
        $sumCents = 0;
        $currency = 'usd';

        $stripeAccount = $this->stripeConnectManager->getByUser($this->user);

        if ($stripeAccount) {
            $wireTransfers = $this->stripeTransactionsManager
                ->setAccount($stripeAccount)
                ->getTransfers([
                    'from' => $from,
                    'to' => $to,
                ]);

            foreach ($wireTransfers as $transfer) {
                if ($transfer->getType() === 'wire'
                    //&& $transfer->getCustomerUserGuid() != $this->config->get('pro')['handler']
                ) {
                    $sumCents += $transfer->getNet();
                    $currency = $transfer->getCurrency();
                }
            }
        }

        $tokensSum = $this->wireSums
            ->setReceiver($this->user->getGuid())
            ->setFrom($from)
            ->setTo($to)
            ->getReceived();

        $earningsGroupModel =  new EarningsGroupModel();
        $earningsGroupModel->setId('wire');

        $wireEarnings = new EarningsItemModel();
        $wireEarnings->setId('wire-all');
        $wireEarnings->setAmountCents($sumCents);
        $wireEarnings->setCurrency($currency);
        $wireEarnings->setAmountTokens($tokensSum);

        $earningsGroupModel->setItems([ $wireEarnings ]);
        $earningsGroupModel->setCurrency($currency);
        return $earningsGroupModel;
    }

    /**
     * @param array|Response $earningsDeposits
     * @return EarningsGroupModel[]
     */
    protected function buildPartnerEarningsItemModels(array|Response $earningsDeposits = []): array
    {
        $groups = [];

        $iteration = 0;
        foreach ($earningsDeposits as $earningsDeposit) {
            // var_dump($earningsDeposit->getAmountTokens());
            $earningsDepositItem = $groups[$earningsDeposit->getItem()] ?? new EarningsItemModel();
            $earningsDepositItem->setAmountCents($earningsDepositItem->getAmountCents() + $earningsDeposit->getAmountCents());
            $earningsDepositItem->setAmountTokens(BigDecimal::sum($earningsDepositItem->getAmountTokens(), $earningsDeposit->getAmountTokens()));
            $earningsDepositItem->setCurrency('usd');
            $groups[$earningsDeposit->getItem()] = $earningsDepositItem;
        }

        $this->fixPartnerEarningsItemsTokens($groups);

        return $groups;
    }

    private function fixPartnerEarningsItemsTokens(array &$groups): void
    {
        foreach ($groups as $group) {
            $group->setAmountTokens(BigNumber::toPlain($group->getAmountTokens(), 18));
        }
    }
}
