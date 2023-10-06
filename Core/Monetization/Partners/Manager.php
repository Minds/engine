<?php
/**
 * Manager
 */
namespace Minds\Core\Monetization\Partners;

use Minds\Common\Repository\Response;
use Minds\Core\Analytics\EntityCentric\Manager as EntityCentricManager;
use Minds\Core\Boost\V3\Partners\Manager as BoostPartnersManager;
use Minds\Core\Di\Di;
use Minds\Core\Entities;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Monetization\Partners\Delegates\DepositsDelegate;
use Minds\Core\Monetization\Partners\Delegates\EmailDelegate;
use Minds\Core\Monetization\Partners\Delegates\PayoutsDelegate;
use Minds\Core\Payments\Stripe;
use Minds\Core\Payments\V2\Enums\PaymentMethod;
use Minds\Core\Payments\V2\Enums\PaymentStatus;
use Minds\Core\Payments\V2\Enums\PaymentType;
use Minds\Core\Payments\V2\Manager as PaymentsManager;
use Minds\Core\Payments\V2\PaymentOptions;
use Minds\Core\Plus;
use Minds\Core\Pro;
use Minds\Core\Util\BigNumber;
use Minds\Entities\User;

class Manager
{
    public const BOOST_PARTNER_CENTS = 100;

    /** @var int */
    const PLUS_SHARE_PCT = Plus\Manager::REVENUE_SHARE_PCT; // 25%

    /** @var int */
    const WIRE_REFERRAL_SHARE_PCT = 50; // 50%

    /** @var int */
    const BOOST_PARTNER_REVENUE_SHARE_PCT = BoostPartnersManager::REVENUE_SHARE_PCT; // 50%

    /** @var int */
    const AFFILIATE_REFERRER_SHARE_PCT = 5; // 5%

    /** @var int */
    const MIN_PAYOUT_CENTS = 10000; // $100 USD

    public function __construct(
        private ?RelationalRepository $repository = null,
        private ?EntityCentricManager $entityCentricManager = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Plus\Manager $plusManager = null,
        private ?Stripe\Connect\Manager $connectManager = null,
        private ?Pro\Manager $proManager = null,
        private ?PayoutsDelegate $payoutsDelegate = null,
        private ?EmailDelegate $emailDelegate = null,
        private ?BoostPartnersManager $boostPartnersManager = null,
        private ?PaymentsManager $paymentsManager = null,
        private ?DepositsDelegate $depositsDelegate = null,
        private ?Logger $logger = null
    ) {
        $this->repository ??= Di::_()->get(RelationalRepository::class);
        $this->entityCentricManager ??= new EntityCentricManager();
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->plusManager ??= Di::_()->get('Plus\Manager');
        $this->connectManager ??= Di::_()->get('Stripe\Connect\Manager');
        $this->proManager ??= Di::_()->get('Pro\Manager');
        $this->payoutsDelegate ??= new Delegates\PayoutsDelegate();
        $this->emailDelegate ??= new Delegates\EmailDelegate();
        $this->boostPartnersManager ??= Di::_()->get(BoostPartnersManager::class);
        $this->paymentsManager ??= Di::_()->get(PaymentsManager::class);
        $this->depositsDelegate ??= new DepositsDelegate();

        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * Return a list of earning deposits
     * @param array $opts
     * @return Response
     */
    public function getList(array $opts = []): Response
    {
        return $this->repository->getList(
            from: $opts['from'] ?? null,
            to: $opts['to'] ?? null,
            userGuid: $opts['user_guid'] ?? null,
        );
    }

    /**
     * Add an earnings deposit
     * @param EarningsDeposit $deposit
     * @return bool
     */
    public function add(EarningsDeposit $deposit): bool
    {
        $this->repository->add($deposit);
        return true;
    }

    /**
     * @param array $opts
     * @return iterable
     */
    public function issueDeposits(array $opts = []): iterable
    {
        $opts = array_merge([
            'from' => strtotime('midnight'),
        ], $opts);

        $this->logger->info("Start processing wire deposits");
        yield from $this->issueWireReferralDeposits($opts);

        $this->logger->info("Start processing boost partner deposits");
        yield from $this->issueBoostPartnerDeposits($opts);

        $this->logger->info("Start processing affiliate deposits");
        yield from $this->issueAffiliateDeposits($opts);
    }

    /**
     * Issue deposits for wire share deposits (pay referrals)
     * @param array $opts
     * @return iterable
     */
    protected function issueWireReferralDeposits(array $opts): iterable
    {
        /** @var array */
        $feesToUserGuid = [];

        /** @var iterable */
        $applicationFees = $this->connectManager->getApplicationFees([
            'from' => $opts['from']
        ]);

        foreach ($applicationFees as $fee) {
            $accountId = $fee->account;
            // Get the Stripe Connect account
            $account = $this->connectManager->getByAccountId($accountId);
            if (!$account) {
                continue; // Account not found
            }
            $userGuid = $account->getMetadata()['guid'];
            // Get the User
            $user = $this->entitiesBuilder->single($userGuid);
            if (!$user) {
                continue; // User may have been deleted
            }
            // Get their referrer
            $referrerGuid = $user->referrer;
            if (!$referrerGuid) {
                continue; // if no referrer to skip
            }
            if (!isset($feesToUserGuid[$referrerGuid])) {
                $feesToUserGuid[$referrerGuid] = (float) 0;
            }
            $feesToUserGuid[$referrerGuid] += (float) $fee->amount;
        }

        foreach ($feesToUserGuid as $userGuid => $cents) {
            $cents = $cents / (100 / self::WIRE_REFERRAL_SHARE_PCT);

            $deposit = new EarningsDeposit();
            $deposit->setTimestamp($opts['from'])
                ->setUserGuid($userGuid)
                ->setAmountCents($cents)
                ->setItem('wire_referral');

            if (!($opts['dry-run'] ?? false)) {
                $this->repository->add($deposit);
            } else {
                $this->logger->info('-------------- WIRE PAYOUT DEPOSIT ----------------');
                $this->logger->info('Deposit', $deposit->export());
                $this->logger->info('---------------------------------------------------');
            }

            yield $deposit;
        }
    }

    /**
     * Issue revenue share deposits for boost partners generated views
     * @param array $opts
     * @return iterable
     */
    public function issueBoostPartnerDeposits(array $opts): iterable
    {
        $timestamp = $opts['from'];
        foreach ($this->boostPartnersManager->getRevenueDetails($opts['from'], $opts['to'] ?? null) as $eCPM) {
            $deposit = (new EarningsDeposit())
                ->setTimestamp($timestamp)
                ->setUserGuid($eCPM['served_by_user_guid'])
                ->setAmountCents((($eCPM['cash_ecpm'] * ($eCPM['cash_total_views_served'] / 1000)) * (self::BOOST_PARTNER_REVENUE_SHARE_PCT / 100)) * 100)
                ->setAmountTokens(($eCPM['tokens_ecpm'] * ($eCPM['tokens_total_views_served'] / 1000)) * (self::BOOST_PARTNER_REVENUE_SHARE_PCT / 100))
                ->setItem('boost_partner');

            if (!($opts['dry-run'] ?? false)) {
                $this->repository->add($deposit);
            } else {
                $this->logger->info('-------------- BOOST PARTNER PAYOUT DEPOSIT ----------------');
                $this->logger->info('Boost revenue details', $eCPM);
                $this->logger->info('Deposit', $deposit->export());
                $this->logger->info('---------------------------------------------------');
            }

            yield $deposit;
        }
    }

    /**
     * Calculates and issues affilite earnings
     * Cash only.
     * @param array $opts
     * @return iterable
     */
    public function issueAffiliateDeposits(array $opts): iterable
    {
        $paymentOptions = (new PaymentOptions())
            ->setWithAffiliate(true)
            ->setFromTimestamp($opts['from'])
            ->setToTimestamp($opts['to'] ?? null)
            ->setPaymentTypes([
                PaymentType::MINDS_PRO_PAYMENT,
                PaymentType::BOOST_PAYMENT,
                PaymentType::MINDS_PLUS_PAYMENT
            ])
            ->setPaymentStatus(PaymentStatus::COMPLETED)
            ->setPaymentMethod(PaymentMethod::CASH);

        $deposits = [];

        $referrersDeposits = [];

        /**
         * Iterate through sum of affiliate earnings for time period and deposit
         * Builds in memory $referrersDeposits
         */
        foreach ($this->paymentsManager->getPaymentsAffiliatesEarnings($paymentOptions) as $item) {
            $deposit = (new EarningsDeposit())
                ->setTimestamp($opts['from'])
                ->setUserGuid($item['affiliate_user_guid'])
                ->setAmountCents($item['total_earnings_millis'] / 10)
                ->setItem('affiliate');

            // Save the deposit
            $this->repository->add($deposit);

            // Build the affiliate to see if they have a referrer themselves
            $affiliateUser = $this->entitiesBuilder->single($item['affiliate_user_guid']);

            if ($affiliateUser instanceof User && !empty($affiliateUser->referrer) && ((time() - $affiliateUser->time_created) < 3650 * 86400)) {
                if (!isset($referrersDeposits[$affiliateUser->referrer])) {
                    $referrersDeposits[$affiliateUser->getGuid()] = 0;
                }

                $referrersDeposits[$affiliateUser->referrer] += $item['total_earnings_millis'] * (self::AFFILIATE_REFERRER_SHARE_PCT / 100);
            }

            yield $deposit;

            if (!($affiliateUser instanceof User) || $deposit->getAmountCents() < 1) {
                continue;
            }

            // Emit event
            $this->depositsDelegate->onIssueAffiliateDeposit($affiliateUser, $deposit);
        }

        /**
         * Iterate through in memory $referrersDeposits and issue the deposit
         */
        foreach ($referrersDeposits as $referrerGuid => $referrersDepositAmountMillis) {
            $deposit = (new EarningsDeposit())
                ->setTimestamp($opts['from'])
                ->setUserGuid($referrerGuid)
                ->setAmountCents($referrersDepositAmountMillis / 10)
                ->setItem('affiliate_referrer');

            // Save the deposit
            $this->repository->add($deposit);

            yield $deposit;

            $referrer = $this->entitiesBuilder->single($referrerGuid);

            if (!($referrer instanceof User) || $deposit->getAmountCents() < 1) {
                continue;
            }

            // Emit event
            $this->depositsDelegate->onIssueAffiliateReferrerDeposit($referrer, $deposit);
        }
    }

    /**
     * Return balance for a user
     * @param User $user
     * @param int $asOfTs
     * @return EarningsBalance
     */
    public function getBalance(User $user, $asOfTs = null): EarningsBalance
    {
        return $this->repository->getBalance((string) $user->getGuid(), $asOfTs);
    }

    /**
     * Returns user's balance for a specific item
     * @param User $user
     * @param array $items
     * @param int|null $asOfTs
     * @return EarningsBalance
     */
    public function getBalanceByItem(User $user, array $items, ?int $asOfTs = null): EarningsBalance
    {
        return $this->repository->getBalanceByItem((string) $user->getGuid(), $items, $asOfTs);
    }

    /**
     * @param array $opts
     * @return iterable
     */
    public function issuePayouts(array $opts): iterable
    {
        $from = $opts['from'] ?? 0;
        $to = $opts['to'] ?? time();
        foreach ($this->repository->getBalancesPerUser(toTimestamp: $to, minBalance: self::MIN_PAYOUT_CENTS) as $earningsBalance) {
            $user = $this->entitiesBuilder->single($earningsBalance->getUserGuid());
            if (!$user) {
                continue;
            }
            if (!$user instanceof User) {
                continue;
            }
            if ($user->getGuid() == "010010110110111101101100011101000110111101101110011011010110000101110011011101000110100101101110") {
                continue;
            }

            $earningsBalance->setAmountCents($this->getBalance($user, $to)->getAmountCents());
            if ($earningsBalance->getAmountCents() < self::MIN_PAYOUT_CENTS) {
                continue;
            }

            if (strlen($earningsBalance->getUserGuid()) > 20) {
                // TODO: move this fix to entitiesBuilder
                continue;
            }

            $proSettings = $this->proManager->setUser($user)->get();
            $payoutMethod = $proSettings->getPayoutMethod();

            if (!$user->isPlus()) {
                //echo "\n do not payout $user->username, they are no longer pro";
                continue;
            }

            if ($user->guid == '1006705312268296208') {
                //echo "\n do not payout $user->username, they are suspicous";
                continue;
            }

            if (in_array($user->guid, ['100000000000035825', '100000000000000519', '240489236636110848', '1110292844016312335', '1107553086722809873' ], true)) {
                //echo "\n skipping $user->username";
                continue;
            }

            $earningsPayout = new EarningsPayout();
            $earningsPayout->setUserGuid($earningsBalance->getUserGuid())
                ->setUser($user)
                ->setAmountCents($earningsBalance->getAmountCents())
                ->setMethod($payoutMethod);

            // Issues the payout
            $this->issuePayout($earningsPayout);

            yield $earningsPayout;
        }

        return;
    }

    /**
     * Issues a single payout
     * @param EarningsPayout $earningsPayout
     * @return void
     */
    public function issuePayout(EarningsPayout $earningsPayout): void
    {
        switch ($earningsPayout->getMethod()) {
            case "usd":
                $user = $earningsPayout->getUser();
                $stripeId = $earningsPayout->getUser()->getMerchant()['id'];
                $earningsPayout->setDestinationId($stripeId);
                if ($stripeId) {
                    echo " paying out $user->username";
                    $this->payoutsDelegate->onUsdPayout($earningsPayout);
                }
                //$this->emailDelegate->onIssuePayout($earningsPayout);
                break;
            case "eth":
                //$this->emailDelegate->onIssuePayout($earningsPayout);
                break;
            case "tokens":
                $amount = BigNumber::toPlain(($earningsPayout->getAmountCents() / 100) * 1.25, 18);

                $offChainTransactions = Di::_()->get('Blockchain\Wallets\OffChain\Transactions');
                $offChainTransactions
                    ->setType('Pro Payout')
                    ->setUser($earningsPayout->getUser())
                    ->setAmount((string) $amount)
                    ->create();
                break;
            default:
                return;
                // TODO:
        }

        $deposit = new EarningsDeposit();
        $deposit->setUserGuid($earningsPayout->getUserGuid())
            ->setTimestamp(time())
            ->setAmountCents($earningsPayout->getAmountCents() * -1)
            //->setMethod('usd')
            ->setItem('payout');
        $this->add($deposit);
    }

    /**
     * @param User $user
     * @param int $rpmCents
     * @return void
     */
    public function resetRpm($user, $rpmCents): void
    {
        $user->setPartnerRpm($rpmCents);
        $save = new Entities\Actions\Save();
        $save->setEntity($user)->save();
    }
}
