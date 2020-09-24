<?php
/**
 * Manager
 */
namespace Minds\Core\Monetization\Partners;

use Minds\Core\Analytics\EntityCentric\Manager as EntityCentricManager;
use Minds\Core\EntitiesBuilder;
use Minds\Common\Repository\Response;
use Minds\Core\Di\Di;
use Minds\Core\Plus;
use Minds\Core\Payments\Stripe;
use Minds\Core\Pro;
use Minds\Core\Util\BigNumber;
use Minds\Entities\User;
use Minds\Core\Entities;
use DateTime;

class Manager
{
    /** @var int */
    const VIEWS_RPM_CENTS = 1000; // $10 USD

    /** @var int */
    const REFERRAL_CENTS = 10; // $0.10

    /** @var int */
    const PLUS_SHARE_PCT = Plus\Manager::REVENUE_SHARE_PCT; // 25%

    /** @var int */
    const WIRE_REFERRAL_SHARE_PCT = 100; // 100%

    /** @var int */
    const MIN_PAYOUT_CENTS = 10000; // $100 USD

    /** @var Repository */
    private $repository;

    /** @var EntityCentricManager */
    private $entityCentricManager;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var Plus\Manager */
    private $plusManager;

    /** @var Stripe\Connect\Manager */
    private $connectManager;

    /** @var Sums */
    private $sums;

    /** @var Pro\Manager */
    private $proManager;

    /** @var Delegates\PayoutsDelegate */
    private $payoutsDelegate;

    /** @var Delegates\EmailDelegate */
    private $emailDelegate;

    public function __construct(
        $repository = null,
        $entityCentricManager = null,
        $entitiesBuilder = null,
        $plusManager = null,
        $connectManager = null,
        $sums = null,
        $proManager = null,
        $payoutsDelegate = null,
        $emailDelegate = null
    ) {
        $this->repository = $repository ?? new Repository();
        $this->entityCentricManager = $entityCentricManager ?? new EntityCentricManager();
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->plusManager = $plusManager ?? Di::_()->get('Plus\Manager');
        $this->connectManager = $connectManager ?? Di::_()->get('Stripe\Connect\Manager');
        $this->sums = $sums ?? new Sums();
        $this->proManager = $proManager ?? Di::_()->get('Pro\Manager');
        $this->payoutsDelegate = $payoutsDelegate ?? new Delegates\PayoutsDelegate();
        $this->emailDelegate = $emailDelegate ?? new Delegates\EmailDelegate();
    }

    /**
     * Return a list of earning deposits
     * @param array $opts
     * @return Response
     */
    public function getList(array $opts = []): Response
    {
        return $this->repository->getList($opts);
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

        yield from $this->issueWireReferralDeposits($opts);
        yield from $this->issuePlusDeposits($opts);
        yield from $this->issuePageviewDeposits($opts);
        yield from $this->issueReferralDeposits($opts);
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
            if (!isset($feesToUser[$referrerGuid])) {
                $feesToUserGuid[$referrerGuid] = (float) 0;
            }
            $feesToUserGuid[$referrerGuid] += (float) $fee->amount;
        }

        foreach ($feesToUserGuid as $userGuid => $cents) {
            $deposit = new EarningsDeposit();
            $deposit->setTimestamp($opts['from'])
                ->setUserGuid($userGuid)
                ->setAmountCents($cents)
                ->setItem('wire_referral');

            $this->repository->add($deposit);

            yield $deposit;
        }
    }

    /**
     * Issue deposits for plus
     * @param array $opts
     * @return iterable
     */
    protected function issuePlusDeposits(array $opts): iterable
    {
        $revenueUsd = $this->plusManager->getDailyRevenue($opts['from']) * (self::PLUS_SHARE_PCT / 100);
        $revenueCents = round($revenueUsd * 100, 0);

        foreach ($this->plusManager->getUnlocks($opts['from']) as $unlock) {
            $shareCents = $revenueCents * $unlock['sharePct'];
            $deposit = new EarningsDeposit();
            $deposit->setTimestamp($opts['from'])
                ->setUserGuid($unlock['user_guid'])
                ->setAmountCents($shareCents)
                ->setItem('plus');

            $this->repository->add($deposit);

            yield $deposit;
        }
    }

    /**
     * Issuse the pageview deposits
     * @param array
     * @return iterable
     */
    protected function issuePageviewDeposits(array $opts): iterable
    {
        $users = [];

        $opts = [
            'fields' => [ 'views::single' ],
            'from' => $opts['from'],
            'owner_guid' => $opts['user_guid'] ?? null,
        ];

        foreach ($this->entityCentricManager->getListAggregatedByOwner($opts) as $ownerSum) {
            $views = $ownerSum['views::single']['value'];
            $amountCents = ($views / 1000) * static::VIEWS_RPM_CENTS;

            if ($amountCents < 1) { // Has to be at least 1 cent / $0.01
                continue;
            }

            // Is this user in the pro program?
            $owner = $this->entitiesBuilder->single($ownerSum['key']);
            if (!$owner) {
                continue;
            }
            if ($rpm = $owner->getPartnerRpm()) {
                if ($rpm) {
                    $amountCents = ($views / 1000) * (int) $rpm;
                    if ($amountCents < 1) { // Has to be at least 1 cent / $0.01
                        $amountCents = 0;
                    }
                }
            }

            if (!$owner || !$owner->isPro()) {
                continue;
            }

            $deposit = new EarningsDeposit();
            $deposit->setTimestamp($opts['from'])
                ->setUserGuid($ownerSum['key'])
                ->setAmountCents($amountCents)
                ->setItem("views");

            $this->repository->add($deposit);

            yield $deposit;
        }
    }

    /**
     * Issue the referral deposits
     * @param array
     * @return iterable
     */
    protected function issueReferralDeposits(array $opts): iterable
    {
        if ($opts['user_guid']) {
            return;
        }
        foreach ($this->entityCentricManager->getListAggregatedByOwner([
            'fields' => [ 'referral::active' ],
            'from' => strtotime('-7 days', $opts['from']),
        ]) as $ownerSum) {
            $count = $ownerSum['referral::active']['value'];
            $amountCents = $count * static::REFERRAL_CENTS;

            // Is this user in the pro program?
            $owner = $this->entitiesBuilder->single($ownerSum['key']);

            if (!$owner || !$owner->isPro()) {
                continue;
            }

            $deposit = new EarningsDeposit();
            $deposit->setTimestamp($opts['from'])
                ->setUserGuid($ownerSum['key'])
                ->setAmountCents($amountCents)
                ->setItem("referrals");

            $this->repository->add($deposit);

            yield $deposit;
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

    /*
     * @param array $opts
     * @return iterable
     */
    public function issuePayouts(array $opts): iterable
    {
        $from = $opts['from'] ?? 0;
        $to = $opts['to'] ?? time();
        foreach ($this->getTotalEarningsForOwners([ 'to' => $to ]) as $earningsBalance) {
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

            $earningsBalance->setAmountCents($this->getBalance($user, $to / 1000)->getAmountCents());
            if ($earningsBalance->getAmountCents() < self::MIN_PAYOUT_CENTS) {
                continue;
            }

            if (strlen($earningsBalance->getUserGuid()) > 20) {
                // TODO: move this fix to entitiesBuilder
                continue;
            }

            // Calculate the ratio of posts to earnings
            $ratio = $this->calculatePostsBalanceRatio($earningsBalance);
            
            if ($this->calculatePostsBalanceRatio($earningsBalance) < 0.01 && $user->getPartnerRpm() > 100) {
                //echo "\n do not payout $user->username ratio:$ratio";
                continue;
            }

            $proSettings = $this->proManager->setUser($user)->get();
            $payoutMethod = $proSettings->getPayoutMethod();

            if (!$this->proManager->setUser($user)->isActive()) {
                //echo "\n do not payout $user->username, they are no longer pro";
                continue;
            }

            if ($user->guid == '1006705312268296208') {
                //echo "\n do not payout $user->username, they are suspicous";
                continue;
            }

            if (in_array($user->guid, ['100000000000035825', '100000000000000519', '240489236636110848', '1110292844016312335', '100000000000000341', '1107553086722809873' ], true)) {
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
                $this->emailDelegate->onIssuePayout($earningsPayout);
                break;
            case "eth":
                $this->emailDelegate->onIssuePayout($earningsPayout);
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
     * Get all earnings for owners
     * @param array $opts
     * @return iterable
     */
    public function getTotalEarningsForOwners($opts = []): iterable
    {
        return $this->sums->getTotalEarningsForOwners($opts);
    }

    /**
     * @param EarningsBalance $earningsBalance
     * @return float
     */
    private function calculatePostsBalanceRatio(EarningsBalance $earningsBalance): float
    {
        $earningsPerPost = $earningsBalance->getAmountCents() / $this->sums->getPostsPerOwner([ $earningsBalance->getUserGuid() ])[$earningsBalance->getUserGuid()];

        return ($earningsPerPost / $earningsBalance->getAmountCents()) * 100;
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
