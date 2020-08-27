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
use Minds\Entities\User;
use DateTime;

class Manager
{
    /** @var int */
    const VIEWS_RPM_CENTS = 1000; // $10 USD

    /** @var int */
    const REFERRAL_CENTS = 10; // $0.10

    /** @var int */
    const PLUS_SHARE_PCT = Plus\Manager::REVENUE_SHARE_PCT; // 25%

    /** @var Repository */
    private $repository;

    /** @var EntityCentricManager */
    private $entityCentricManager;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var Plus\Manager */
    private $plusManager;

    public function __construct(
        $repository = null,
        $entityCentricManager = null,
        $entitiesBuilder = null,
        $plusManager = null
    ) {
        $this->repository = $repository ?? new Repository();
        $this->entityCentricManager = $entityCentricManager ?? new EntityCentricManager();
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->plusManager = $plusManager ?? Di::_()->get('Plus\Manager');
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

        yield from $this->issuePlusDeposits($opts);
        yield from $this->issuePageviewDeposits($opts);
        yield from $this->issueReferralDeposits($opts);
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

        foreach ($this->entityCentricManager->getListAggregatedByOwner([
            'fields' => [ 'views::single' ],
            'from' => $opts['from'],
        ]) as $ownerSum) {
            $views = $ownerSum['views::single']['value'];
            $amountCents = ($views / 1000) * static::VIEWS_RPM_CENTS;

            if ($amountCents < 1) { // Has to be at least 1 cent / $0.01
                continue;
            }

            // Is this user in the pro program?
            $owner = $this->entitiesBuilder->single($ownerSum['key']);

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
     * @return EarningsBalance
     */
    public function getBalance(User $user): EarningsBalance
    {
        return $this->repository->getBalance((string) $user->getGuid());
    }
}
