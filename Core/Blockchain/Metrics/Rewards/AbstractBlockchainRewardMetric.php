<?php
namespace Minds\Core\Blockchain\Metrics\Rewards;

use Brick\Math\BigDecimal;
use Cassandra\Date;
use Minds\Core\Blockchain\Metrics\AbstractBlockchainMetric;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared;
use Minds\Core\Di\Di;

abstract class AbstractBlockchainRewardMetric extends AbstractBlockchainMetric
{
    /** @var Client */
    protected $cql;

    public function __construct($cql = null, ...$injectables)
    {
        parent::__construct(...$injectables);
        $this->cql = $cql ?? Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * Return the global score
     * @param string $rewardType
     * @return BigDecimal
     */
    protected function getScore($rewardType): BigDecimal
    {
        $statement = "SELECT SUM(score) as score
            FROM token_rewards_by_date
            WHERE date = ?
            AND reward_type = ?";

        $values = [
            new Date($this->to),
            $rewardType
        ];

        $prepared = new Prepared\Custom();
        $prepared->query($statement, $values);

        $result = $this->cql->request($prepared);
        
        if (!$result || !$result[0]) {
            return BigDecimal::of(0);
        }

        return BigDecimal::of((string) $result[0]['score']);
    }
}
