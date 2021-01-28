<?php
/**
 * Syncs a users contributions to rewards values
 */
namespace Minds\Core\Rewards;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Minds\Common\Repository\Response;
use Minds\Core\Analytics\UserStates\UserActivityBuckets;
use Minds\Core\Blockchain\Transactions\Repository as TxRepository;
use Minds\Core\Blockchain\Wallets\OffChain\Transactions;
use Minds\Core\Blockchain\Transactions\Transaction;
use Minds\Core\Blockchain\LiquidityPositions;
use Minds\Core\Blockchain\Services\BlockFinder;
use Minds\Core\Blockchain\Token;
use Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain;
use Minds\Core\Di\Di;
use Minds\Entities\User;
use Minds\Core\Guid;
use Minds\Core\Rewards\Contributions\ContributionQueryOpts;
use Minds\Core\Util\BigNumber;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Rewards\TokenomicsManifests\TokenomicsManifestInterface;
use Minds\Core\Rewards\TokenomicsManifests\TokenomicsManifestV2;
use Minds\Core\Log\Logger;

class Manager
{
    /** @var string[] */
    const REWARD_TYPES = [
        self::REWARD_TYPE_ENGAGEMENT,
        self::REWARD_TYPE_LIQUIDITY,
        self::REWARD_TYPE_HOLDING,
    ];

    /** @var string */
    const REWARD_TYPE_ENGAGEMENT = 'engagement';

    /** @var string */
    const REWARD_TYPE_LIQUIDITY = 'liquidity';

    /** @var string */
    const REWARD_TYPE_HOLDING = 'holding';

    /** @var Contributions\Manager */
    protected $contributions;

    /** @var Transactions */
    protected $transactions;

    /** @var TxRepository */
    protected $txRepository;

    /** @var Ethereum */
    protected $eth;

    /** @var Config */
    protected $config;

    /** @var Repository */
    protected $repository;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var LiquidityPositions\Manager */
    protected $liquidityPositionsManager;

    /** @var UniqueOnChain\Manager */
    protected $uniqueOnChainManager;

    /** @var Token */
    protected $token;

    /** @var Logger */
    protected $logger;

    /** @var User $user */
    protected $user;

    /** @var int $from */
    protected $from;

    /** @var int $to */
    protected $to;

    /** @var bool $dryRun */
    protected $dryRun = false;

    public function __construct(
        $contributions = null,
        $transactions = null,
        $txRepository = null,
        $eth = null,
        $config = null,
        $repository = null,
        $entitiesBuilder = null,
        $liquidityPositionManager = null,
        $uniqueOnChainManager = null,
        $blockFinder = null,
        $token = null,
        $logger = null
    ) {
        $this->contributions = $contributions ?: new Contributions\Manager;
        $this->transactions = $transactions ?: Di::_()->get('Blockchain\Wallets\OffChain\Transactions');
        $this->txRepository = $txRepository ?: Di::_()->get('Blockchain\Transactions\Repository');
        $this->eth = $eth ?: Di::_()->get('Blockchain\Services\Ethereum');
        $this->config = $config ?: Di::_()->get('Config');
        $this->repository = $repository ?? new Repository();
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->liquidityPositionsManager = $liquidityPositionManager ?? Di::_()->get('Blockchain\LiquidityPositions\Manager');
        $this->uniqueOnChainManager = $uniqueOnChainManager ?? Di::_()->get('Blockchain\Wallets\OnChain\UniqueOnChain\Manager');
        $this->blockFinder = $blockFinder ?? Di::_()->get('Blockchain\Services\BlockFinder');
        $this->token = $token ?? Di::_()->get('Blockchain\Token');
        $this->logger = $logger ?? Di::_()->get('Logger');
        $this->from = strtotime('-7 days') * 1000;
        $this->to = time() * 1000;
    }

    /**
     * @param User $user
     * @return Manager
     */
    public function setUser($user): Manager
    {
        $manager = clone $this;
        $manager->user = $user;
        return $manager;
    }

    /**
     * Does not issue tokens!
     * @param RewardEntry $rewardEntry
     * @return bool
     */
    public function add(RewardEntry $rewardEntry): bool
    {
        //
        return $this->repository->add($rewardEntry);
    }

    /**
     * @param RewardsQueryOpts $opts (optional)
     * @return Response
     */
    public function getList(RewardsQueryOpts $opts = null): Response
    {
        return $this->repository->getList($opts);
    }

    /**
     * @param RewardsQueryOpts $opts (optional)
     * @return RewardsSummary
     */
    public function getSummary(RewardsQueryOpts $opts = null): RewardsSummary
    {
        $rewardEntries = $this->getList($opts);

        $rewardsSummary = new RewardsSummary();
        $rewardsSummary->setUserGuid($opts->getUserGuid())
            ->setDateTs($opts->getDateTs())
            ->setRewardEntries($rewardEntries->toArray());

        return $rewardsSummary;
    }


    /**
     * @return void
     */
    public function calculate(RewardsQueryOpts $opts = null): void
    {
        $opts = $opts ?? (new RewardsQueryOpts())
            ->setDateTs(time());

        ////
        // First, work out our scores
        ////

        // Engagement rewards

        $opts = (new ContributionQueryOpts())
            ->setDateTs($opts->getDateTs());

        foreach ($this->contributions->getSummaries($opts) as $i => $contributionSummary) {

            /** @var User */
            $user = $this->entitiesBuilder->single($contributionSummary->getUserGuid());
            if (!$user) {
                continue;
            }

            // TODO: use a getKiteState function instead...
            switch ($user->kite_state) {
                case UserActivityBuckets::STATE_CORE:
                    $multiplier = 3;
                    break;
                case UserActivityBuckets::STATE_CASUAL:
                    $multiplier = 2;
                    break;
                case UserActivityBuckets::STATE_CURIOUS:
                    $multiplier = 1;
                    break;
                default:
                    $multiplier = 1;
            }

            $score = BigDecimal::of($contributionSummary->getScore())->multipliedBy($multiplier);

            $rewardEntry = new RewardEntry();
            $rewardEntry->setUserGuid($contributionSummary->getUserGuid())
                ->setDateTs($contributionSummary->getDateTs())
                ->setRewardType(static::REWARD_TYPE_ENGAGEMENT)
                ->setScore($score)
                ->setMultiplier($multiplier);

            //

            $this->add($rewardEntry);

            $this->logger->info("[$i]: Engagement score calculated as $score", [
                'userGuid' => $rewardEntry->getUserGuid(),
                'reward_type' => $rewardEntry->getRewardType(),
            ]);
        }


        // Liquidity rewards

        foreach ($this->liquidityPositionsManager->setDateTs($opts->getDateTs())->getAllProvidersSummaries() as $i => $liquiditySummary) {
            $multiplier = 1; // TODO: check if we had a score yesterday and then increment the multiplier respectfully

            $score = $liquiditySummary->getUserLiquidityTokens()->multipliedBy($multiplier);

            $rewardEntry = new RewardEntry();
            $rewardEntry->setUserGuid($liquiditySummary->getUserGuid())
                ->setDateTs($opts->getDateTs())
                ->setRewardType(static::REWARD_TYPE_LIQUIDITY)
                ->setScore($score)
                ->setMultiplier($multiplier);

            $this->add($rewardEntry);

            $this->logger->info("[$i]: Liquidity score calculated as $score", [
                'userGuid' => $rewardEntry->getUserGuid(),
                'reward_type' => $rewardEntry->getRewardType(),
            ]);
        }

        // Holding rewards
        $blockNumber = $this->blockFinder->getBlockByTimestamp($opts->getDateTs());
        foreach ($this->uniqueOnChainManager->getAll() as $i => $uniqueOnChain) {

             /** @var User */
            $user = $this->entitiesBuilder->single($uniqueOnChain->getUserGuid());
            if (!$user) {
                continue;
            }

            $tokenBalance = $this->token->fromTokenUnit(
                $this->token->balanceOf($uniqueOnChain->getAddress(), $blockNumber)
            );

            $multiplier = 1;
            $score = BigDecimal::of($tokenBalance)->multipliedBy($multiplier);

            $rewardEntry = new RewardEntry();
            $rewardEntry->setUserGuid($user->getGuid())
                ->setDateTs($opts->getDateTs())
                ->setRewardType(static::REWARD_TYPE_HOLDING)
                ->setScore($score)
                ->setMultiplier($multiplier);

            $this->add($rewardEntry);

            $this->logger->info("[$i]: Holding score calculated as $score", [
                'userGuid' => $rewardEntry->getUserGuid(),
                'reward_type' => $rewardEntry->getRewardType(),
                'blockNumber' => $blockNumber,
                'tokenBalance' => $tokenBalance,
            ]);
        }

        //

        ////
        // Then, work out the tokens based off our score / globalScore
        ////

        foreach ($this->repository->getIterator($opts) as $i => $rewardEntry) {
            if ($rewardEntry->getSharePct() === (float) 0) {
                continue;
            }

            // Get the pool
            $tokenomicsManifest = $this->getTokenomicsManifest($rewardEntry->getTokenomicsVersion());
            $tokenPool = BigDecimal::of($tokenomicsManifest->getDailyPools()[$rewardEntry->getRewardType()]);

            $tokenAmount = $tokenPool->multipliedBy($rewardEntry->getSharePct(), 18, RoundingMode::FLOOR);

            $rewardEntry->setTokenAmount($tokenAmount);
            $this->add($rewardEntry);
        
            $sharePct = $rewardEntry->getSharePct() * 100;

            $this->logger->info("[$i]: Issued $tokenAmount tokens ($sharePct%)", [
                'userGuid' => $rewardEntry->getUserGuid(),
                'reward_type' => $rewardEntry->getRewardType(),
            ]);
        }
    }

    /**
     * Issue tokens based on alreay calculated RewardEntry's
     * @return void
     */
    public function issueTokens(): void
    {
        // TODO
    }

    /**
     * @param int $tokenomicsVersion
     * @return TokenomicsManifestInterface
     */
    private function getTokenomicsManifest($tokenomicsVersion = 1): TokenomicsManifestInterface
    {
        switch ($tokenomicsVersion) {
            case 2:
                return new TokenomicsManifestV2();
                break;
            default:
                throw new \Exception("Invalid tokenomics version");
        }
    }

    ////
    // Legacy
    ////

    /**
     * Sets if to dry run or not. A dry run will return the data but will save
     * to the database
     * @param bool $dryRun
     * @return $this
     */
    public function setDryRun($dryRun)
    {
        $this->dryRun = $dryRun;
        return $this;
    }

    public function setFrom($from)
    {
        $this->from = $from;
        return $this;
    }

    public function setTo($to)
    {
        $this->to = $to;
        return $this;
    }

    public function sync()
    {
        //First double check that we have not already credited them any
        //rewards for this timeperiod
        $transactions = $this->txRepository->getList([
            'user_guid' => $this->user->guid,
            'wallet_address' => 'offchain', //removed because of allow filtering issues.
            'timestamp' => [
                'gte' => $this->from,
                'lte' => $this->to,
                'eq' => null,
            ],
            'contract' => 'offchain:reward',
        ]);
        
        if ($transactions['transactions']) {
            throw new \Exception("Already issued rewards to this user");
        }

        $this->contributions
            ->setFrom($this->from)
            ->setTo($this->to)
            ->setUser($this->user);

        if ($this->user) {
            $this->contributions->setUser($this->user);
        }

        $amount = $this->contributions->getRewardsAmount();
 
        $transaction = new Transaction();
        $transaction
            ->setUserGuid($this->user->guid)
            ->setWalletAddress('offchain')
            ->setTimestamp(strtotime("+24 hours - 1 second", $this->from / 1000))
            ->setTx('oc:' . Guid::build())
            ->setAmount($amount)
            ->setContract('offchain:reward')
            ->setCompleted(true);

        if ($this->dryRun) {
            return $transaction;
        }

        $this->txRepository->add($transaction);

        try {
            $this->bonus();
        } catch (\Exception $e) {
        }

        //$this->txRepository->delete($this->user->guid, strtotime("+24 hours - 1 second", $this->from / 1000), 'offchain');
        return $transaction;
    }

    public function bonus()
    {
        $this->contributions
            ->setFrom($this->from)
            ->setTo($this->to)
            ->setUser($this->user);

        if (!$this->user || !$this->user->eth_wallet) {
            return;
        }

        $amount = $this->contributions->getRewardsAmount();

        if ($amount <= 0) {
            return;
        }

        $res = $this->eth->sendRawTransaction($this->config->get('blockchain')['contracts']['bonus']['wallet_pkey'], [
            'from' => $this->config->get('blockchain')['contracts']['bonus']['wallet_address'],
            'to' => $this->config->get('blockchain')['token_address'],
            'gasLimit' => BigNumber::_(4612388)->toHex(true),
            'gasPrice' => BigNumber::_(10000000000)->toHex(true),
            //'nonce' => (int) microtime(true),
            'data' => $this->eth->encodeContractMethod('transfer(address,uint256)', [
                $this->user->eth_wallet,
                BigNumber::_($amount)->mul(0.25)->toHex(true),
            ])
        ]);
        
        $transaction = new Transaction();
        $transaction
            ->setUserGuid($this->user->guid)
            ->setWalletAddress($this->user->eth_wallet)
            ->setTimestamp(time())
            ->setTx($res)
            ->setAmount((string) BigNumber::_($amount)->mul(0.25))
            ->setContract('bonus')
            ->setCompleted(true);
    
        $this->txRepository->add($transaction);
        return $transaction;
    }
}
