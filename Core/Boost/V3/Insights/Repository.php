<?php
namespace Minds\Core\Boost\V3\Insights;

use Minds\Core\Data\MySQL\Client;
use PDO;
use Selective\Database\Connection;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class Repository
{
    protected Connection $mysqlQueryBuilder;

    public function __construct(private ?Client $mysqlClient = null)
    {
        $this->mysqlClient ??= new Client();
        $this->mysqlQueryBuilder = new Connection($this->mysqlClient->getConnection(Client::CONNECTION_REPLICA));
    }

    /**
     * Returns historical cpms of completed boosts
     * @param int $targetAudience
     * @param int $targetLocation
     * @param int $paymentMethod
     * @return float[]
     */
    public function getHistoricCpms(
        int $targetAudience,
        int $targetLocation,
        int $paymentMethod
    ): array {
        $numDays = 3;

        $values = [
            'from_timestamp' => date('c', strtotime($numDays . ' days ago')),
            'payment_method' => $paymentMethod,
            'target_location' => $targetLocation,
            'target_audience' => $targetAudience,
        ];

        $boostTotalViewsQuery = $this->mysqlQueryBuilder
            ->select()
            ->columns([
                'guid',
                'total_views' => new RawExp('SUM(views)'),
            ])
            ->from('boost_summaries')
            ->groupBy('guid')
            ->alias('boost_summaries');

        $query = $this->mysqlQueryBuilder
            ->select()
            ->columns([
                'cpm' => new RawExp('MAX((daily_bid / (total_views / duration_days)) * 1000)'),
            ])
            ->from('boosts')
            ->innerJoin(
                new RawExp(rtrim($boostTotalViewsQuery->build(), ';')),
                'boosts.guid',
                Operator::EQ,
                'boost_summaries.guid'
            )
            ->where('completed_timestamp', Operator::GTE, new RawExp(':from_timestamp'))
            ->where('payment_method', Operator::EQ, new RawExp(':payment_method'))
            ->where('total_views', Operator::GT, new RawExp("1"))
            ->where('target_location', Operator::EQ, new RawExp(':target_location'))
            ->where('target_suitability', Operator::GTE, new RawExp(':target_audience'))
            ->where('duration_days', Operator::LTE, new  RawExp((string) $numDays)) // Limiting to the duration that we look back to avoid skew
            ->groupBy('boosts.guid');

        $statement = $query->prepare();

        $this->mysqlClient->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();

        return array_map(function ($val) {
            return (float) $val['cpm'];
        }, $statement->fetchAll(PDO::FETCH_ASSOC));
    }
}
