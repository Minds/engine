<?php
namespace Minds\Core\Blockchain\Metrics;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\DivisionByZeroException;
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
     * @param MetricQueryOpts $opts
     * @return Response
     */
    public function getList($opts): Response
    {
        $statement = "SELECT metric, date, offchain, onchain 
            FROM token_metrics
            WHERE metric = ?";
        $values = [ $opts->getMetricId() ];

        if ($opts->getTimestamp()) {
            $statement .= " AND date = ? AND hour = ?";
            $values[] =
                new Date($opts->getTimestamp());
            $values[] = (int) date('H', $opts->getTimestamp());
        }
            
        $statement .= " GROUP BY metric, date";

        $response = new Response();

        $prepared = new Prepared\Custom();
        $prepared->query($statement, $values);

        $result = $this->cql->request($prepared);

        if (!$result || !$result[0]) {
            return $response;
        }

        foreach ($result as $row) {
            $className = "Minds\Core\Blockchain\Metrics\\{$row['metric']}";
            $metric = new $className;

            $metric->setOffchain(BigDecimal::of($row['offchain']))
                ->setOnchain(BigDecimal::of($row['onchain']));

            $response[] = $metric;
        }

        return $response;
    }

    /**
     * @param AbstractBlockchainMetric $metric
     * @return bool
     */
    public function add(AbstractBlockchainMetric $metric): bool
    {
        $statement = "INSERT INTO token_metrics (metric, date, hour, offchain, onchain) VALUES (?,?,?,?,?)";
        $values = [
            $metric->getId(),
            new Date($metric->getTimestamp()),
            (int) date('H', $metric->getTimestamp()),
            new Decimal((string) $metric->getOffchain()),
            new Decimal((string) $metric->getOnchain()),
        ];

        $prepared = new Prepared\Custom();
        $prepared->query($statement, $values);

        return (bool) $this->cql->request($prepared);
    }
}
