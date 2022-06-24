<?php
namespace Minds\Core\Rewards;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\RoundingMode;
use Cassandra\Bigint;
use Cassandra\Decimal;
use Cassandra\Date;
use Cassandra\Type;
use Minds\Common\Repository\Response;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Scroll;
use Minds\Core\Data\Cassandra\Prepared;
use Minds\Core\Di\Di;

class Repository
{
    /** @var Client */
    private $cql;

    /** @var Scroll */
    private $scroll;

    /**
     * @param Client $cql
     * @param Scroll $scroll
     */
    public function __construct($cql = null, $scroll = null)
    {
        $this->cql = $cql ?? Di::_()->get('Database\Cassandra\Cql');
        $this->scroll = $scroll ?? Di::_()->get('Database\Cassandra\Cql\Scroll');
    }

    /**
     * @param RewardsQueryOpts $opts
     * @return Response
     */
    public function getList($opts): Response
    {
        $allTime = $this->getAllTimeAggs($opts);
        $daily = [];
        array_push($daily, ...$this->getDailyAggs($opts));
        $dailyGlobal = $this->getDailyGlobalAggs($opts);

        /**
         * Are there any daily RewardEntries mising?
         * This could happen if you didn't get a score for an item
         */
        $rewardTypes = array_map(function ($rewardEntry) {
            return $rewardEntry->getRewardType();
        }, $daily);
        $missingRewardTypes = array_diff(Manager::REWARD_TYPES, $rewardTypes);
        foreach ($missingRewardTypes as $rewardType) {
            $daily[] = (new RewardEntry)
                ->setUserGuid((string) ($opts->getUserGuid()))
                ->setDateTs($opts->getDateTs())
                ->setRewardType($rewardType);
        }

        foreach ($daily as $rewardEntry) {
            $allTimeSummary = $allTime[$rewardEntry->getRewardType()] ?? new RewardEntry();
            $dailyGlobalSummary = $dailyGlobal[$rewardEntry->getRewardType()] ?? new RewardEntry();
            try {
                $sharePct = $rewardEntry->getScore()->dividedBy($dailyGlobalSummary->getScore(), 8, RoundingMode::FLOOR);
                $rewardEntry->setSharePct($sharePct->toFloat());
            } catch (DivisionByZeroException $e) {
            }

            $rewardEntry->setAllTimeSummary($allTimeSummary);
            $rewardEntry->setGlobalSummary($dailyGlobalSummary);
        }

        return new Response($daily);
    }

    /**
     * @param RewardsQueryOpts $opts
     * @return RewardEntry[]
     */
    public function getIterator($opts): iterable
    {
        $daily = $this->getDailyAggs($opts);
        $dailyGlobal = $this->getDailyGlobalAggs($opts);

        foreach ($daily as $rewardEntry) {
            $dailyGlobalSummary = $dailyGlobal[$rewardEntry->getRewardType()] ?? new RewardEntry();
            try {
                $sharePct = $rewardEntry->getScore()->dividedBy($dailyGlobalSummary->getScore(), 8, RoundingMode::FLOOR);
                $rewardEntry->setSharePct($sharePct->toFloat());
            } catch (DivisionByZeroException $e) {
            } catch (NumberFormatException $e) {
            }
            yield $rewardEntry;
        }
    }

    /**
     * @param RewardEntry $rewardEntry
     * @return bool
     */
    public function add(RewardEntry $rewardEntry): bool
    {
        $statement = "INSERT INTO token_rewards (
            user_guid,
            date,
            reward_type,
            score,
            multiplier,
            tokenomics_version
            ) VALUES (?,?,?,?,?,?)";
        $values = [
            new Bigint($rewardEntry->getUserGuid()),
            new Date($rewardEntry->getDateTs()),
            $rewardEntry->getRewardType(),
            new Decimal((string) $rewardEntry->getScore()),
            new Decimal((string) $rewardEntry->getMultiplier()),
            $rewardEntry->getTokenomicsVersion(),
        ];

        $prepared = new Prepared\Custom();
        $prepared->query($statement, $values);
        $prepared->setOpts([
            // Ensure we write to all the entire cluster and not just local
            'consistency' => \Cassandra::CONSISTENCY_QUORUM,
        ]);

        return (bool) $this->cql->request($prepared);
    }

    /**
     * @param RewardEntry $rewardEntry
     * @param array $fields
     * @return bool
     */
    public function update(RewardEntry $rewardEntry, array $fields = []): bool
    {
        $statement = "UPDATE token_rewards";
        $values = [];

        /**
         * Set statement
         */
        $set = [];

        foreach ($fields as $field) {
            switch ($field) {
                case "token_amount":
                    $set["token_amount"] = new Decimal((string) $rewardEntry->getTokenAmount() ?: 0);
                    break;
                case "score":
                    $set["score"] = new Decimal((string) $rewardEntry->getScore() ?: 0);
                    break;
                case "payout_tx":
                    $set["payout_tx"] = $rewardEntry->getPayoutTx();
                    break;
            }
        }

        $statement .= " SET " . implode(' , ', array_map(function ($field) {
            return "$field = ?";
        }, array_keys($set)));
        $values = array_values($set);

        /**
         * Where statement
         */
        $where = [
            "user_guid = ?" => new Bigint($rewardEntry->getUserGuid()),
            "date = ?" =>  new Date($rewardEntry->getDateTs()),
            "reward_type = ?" => $rewardEntry->getRewardType()
        ];

        $statement .= " WHERE " . implode(' AND ', array_keys($where));
        array_push($values, ...array_values($where));

        $prepared = new Prepared\Custom();
        $prepared->query($statement, $values);
        $prepared->setOpts([
            // Ensure we write to all the entire cluster and not just local
            'consistency' => \Cassandra::CONSISTENCY_QUORUM,
        ]);

        return (bool) $this->cql->request($prepared);
    }

    //

    /**
     * @param RewardsQueryOpts $opts
     * @return array
     */
    private function getAllTimeAggs(RewardsQueryOpts $opts): array
    {
        // Get the total rewards, broken out by type
        $statement = "SELECT user_guid, reward_type, SUM(score) AS score, SUM(token_amount) AS token_amount
            FROM token_rewards
            WHERE user_guid = ?
            GROUP BY reward_type";
        $values = [ new Bigint($opts->getUserGuid()) ];

        $prepared = new Prepared\Custom();
        $prepared->query($statement, $values);

        $rewardSummaries = [];
        foreach ($this->getRewardsSummaries($prepared) as $rewardSummary) {
            $rewardSummaries[$rewardSummary->getRewardType()] = $rewardSummary;
        }
        return $rewardSummaries;
    }

    /**
     * @param Prepared\Custom $prepared
     * @return RewardEntry[]
     */
    private function getDailyAggs(RewardsQueryOpts $opts): iterable
    {
        $statement = "SELECT *";

        $where = [
            "reward_type IN ?" => Type::collection(Type::text())->create(...Manager::REWARD_TYPES),
            "date = ?" => new Date($opts->getDateTs()),
        ];
       
        if ($opts->getUserGuid()) {
            $statement .= " FROM token_rewards";
            $where['user_guid = ?'] = new Bigint($opts->getUserGuid());
        } else {
            $statement .= " FROM token_rewards_by_date";
        }
 
        $statement .= " WHERE " . implode(' AND ', array_keys($where));

        // This is all getting messy! (MH)

        if ($opts->getUserGuid()) {
            $statement .= " GROUP BY reward_type";
        } else {
            $statement .= " GROUP BY reward_type, user_guid";
        }

        $values = array_values($where);

        $prepared = new Prepared\Custom();
        $prepared->query($statement, $values);

        return $this->getRewardsSummaries($prepared);
    }

    /**
     * @param Prepared\Custom $prepared
     * @return RewardEntry[]
     */
    private function getDailyGlobalAggs(RewardsQueryOpts $opts): array
    {
        $statement = "SELECT reward_type, date, SUM(score) as score, SUM(token_amount) as token_amount 
            FROM token_rewards_by_date
            WHERE date = ?
            GROUP BY reward_type";
        $values = [ new Date($opts->getDateTs()) ];

        $prepared = new Prepared\Custom();
        $prepared->query($statement, $values);

        $rewardSummaries = [];
        foreach ($this->getRewardsSummaries($prepared) as $rewardSummary) {
            $rewardSummaries[$rewardSummary->getRewardType()] = $rewardSummary;
        }
        return $rewardSummaries;
    }

    /**
     * @param Prepared\Custom $prepared
     * @return RewardEntry[]
     */
    private function getRewardsSummaries(Prepared\Custom $prepared): iterable
    {
        foreach ($this->scroll->request($prepared) as $k => $row) {
            try {
                $rewardType = $row['reward_type'];
                $rewardEntry = (new RewardEntry())
                ->setUserGuid((string) ($row['user_guid'] ?? null))
                ->setDateTs(isset($row['date']) ? $row['date']->seconds() : null)
                ->setRewardType($rewardType)
                ->setScore(BigDecimal::of((string) $row['score']))
                ->setTokenAmount(BigDecimal::of((string) $row['token_amount'] ?: 0))
                ->setPayoutTx((string) ($row['payout_tx'] ?? null));

                if (isset($row['data'])) {
                    $rewardEntry->setDateTs($row['date']->seconds());
                }

                if (isset($row['multiplier'])) {
                    $rewardEntry->setMultiplier(BigDecimal::of($row['multiplier']));
                }

                yield $rewardEntry;
            } catch (NumberFormatException $e) {
            }
        }
    }
}
