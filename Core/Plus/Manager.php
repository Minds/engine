<?php
/**
 * Manager for Plus
 *
 * !! Most of Plus is handled via Discovery or Wire\Paywall !!
 */
namespace Minds\Core\Plus;

use Minds\Core\Di\Di;
use Minds\Core\Config;
use Minds\Core\Data\ElasticSearch;
use Minds\Core\Data\Cassandra;
use Minds\Core\Wire\Paywall\PaywallEntityInterface;
use Minds\Core\Rewards\Contributions\ContributionValues;

class Manager
{
    /** @var Config */
    protected $config;

    /** @var ElasticSearch\Client */
    protected $es;

    /** @var Cassandra\Client */
    protected $db;

    /** @var int */
    const SUBSCRIPTION_PERIOD_MONTH = 30;

    /** @var int */
    const SUBSCRIPTION_PERIOD_YEAR = 365;

    /** @var int */
    const REVENUE_SHARE_PCT = 25;

    public function __construct($config = null, $es = null, $db = null)
    {
        $this->config = $config ?? Di::_()->get('Config');
        $this->es = $es ?? Di::_()->get('Database\ElasticSearch');
        $this->db = $db ?? Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * Returns the plus guid
     * @return string
     */
    public function getPlusGuid(): string
    {
        return $this->config->get('plus')['handler'];
    }

    /**
     * Returns the plus support tier urn
     * @return string
     */
    public function getPlusSupportTierUrn(): string
    {
        return $this->config->get('plus')['support_tier_urn'];
    }

    /**
     * Returns the subscription price
     * @param string $period (month,day)
     * @return int (cents)
     */
    public function getSubscriptionPrice(string $period): int
    {
        /** @var string */
        $key = '';

        switch ($period) {
            case 'month':
                $key = 'monthly';
                break;
            case 'year':
                $key = 'yearly';
                break;
            default:
                throw new \Exception("Subscription can only be month or year");
        }

        return $this->config->get('upgrades')['plus'][$key]['usd'] * 100;
    }

    /**
     * Return sum of revenue for the previous subscriptions period (30 days)
     * Will return in USD
     * @param int $asOfTs
     * @return float
     */
    public function getActiveRevenue($asOfTs = null): float
    {
        $revenue = 0;
        $from = strtotime(self::SUBSCRIPTION_PERIOD_MONTH . " days ago", $asOfTs ?? time());
        $to = strtotime("+" . self::SUBSCRIPTION_PERIOD_MONTH . " days", $from);

        //
        // Sum the wire takings for the previous 30 days where monthly subscription
        //
        $revenue += $this->getRevenue($from, $to, $this->getSubscriptionPrice('month'));

        //
        // Sum the wire takings for the previous 30 days where yearly subscription (amortized to month)
        //
        $from = strtotime(self::SUBSCRIPTION_PERIOD_YEAR . " days ago", $asOfTs ?? time());
        $to = strtotime("+" . self::SUBSCRIPTION_PERIOD_YEAR . " days", $from);
        $revenue += $this->getRevenue($from, $to, $this->getSubscriptionPrice('year')) / 12;

        return round($revenue / 100, 2);
    }

    /**
     * Returns the daily revenue for Plus
     * - Assumptions:
     *   - Subscription is 30 days
     *   - Amoritize the revenue by dividing the revenue
     *     for previous 30 days by 30
     *   - eg: ($300 per month) / 30 = $10 per day
     * Will return in USD
     * @param int $asOfTime
     * @return int
     */
    public function getDailyRevenue($asOfTs = null): float
    {
        return round($this->getActiveRevenue($asOfTs) / self::SUBSCRIPTION_PERIOD_MONTH, 2);
    }

    /**
     * @var int $from
     * @var int $to
     * @return int
     */
    protected function getRevenue(int $from, int $to, int $amount): int
    {
        $query = new Cassandra\Prepared\Custom();

        // ALLOW FILTERING is used to filter by amount. As subscription volume is small
        // and paritioned by receiver_guid, it should not be an issue

        $query->query("SELECT SUM(wei) as wei_sum
          FROM wire
          WHERE receiver_guid=?
          AND method='usd'
          AND timestamp >= ?
          AND timestamp < ?
          AND wei=?
          ALLOW FILTERING
        ", [
            new \Cassandra\Varint($this->getPlusGuid()),
            new \Cassandra\Timestamp($from, 0),
            new \Cassandra\Timestamp($to, 0),
            new \Cassandra\Varint($amount)
        ]);

        try {
            $result = $this->db->request($query);
        } catch (\Exception $e) {
            error_log(print_r($e, true));
            return 0;
        }
        
        return (int) $result[0]['wei_sum'];
    }

    /**
     * Return unlocks (deprecated)
     * @param int $asOfTs
     * @return iterable
     */
    public function getUnlocks(int $asOfTs): array
    {
        return [];
    }

    /**
     * Return the scores of users
     * @param int $asOfTs
     * @return iterable
     */
    public function getScores(int $asOfTs): iterable
    {
        /** @var array */
        $must = [];

        $must[] = [
            'term' => [
                'support_tier_urn' => $this->getPlusSupportTierUrn(),
            ],
        ];

        $must[] = [
            'range' => [
                '@timestamp' => [
                    'gte' => $asOfTs * 1000,
                    'lt' => strtotime('midnight tomorrow', $asOfTs) * 1000,
                ]
            ]
        ];

        $body = [
            'query' => [
                'bool' => [
                    'must' => $must,
                ],
            ],
            'aggs' => [
                'owners' => [
                    'terms' => [
                        'field' => 'entity_owner_guid.keyword',
                        'size' => 5000,
                    ],
                    'aggs' => [
                        'actions' => [
                            'terms' => [
                                'field' => 'action.keyword',
                                'size' => 5000,
                            ],
                            'aggs' => [
                                'unique_user_actions' => [
                                    'cardinality' => [
                                        'field' => 'user_guid.keyword',
                                    ],
                                ]
                            ]
                        ]
                    ]
                ],
            ]
        ];

        $query = [
            'index' => 'minds-metrics-*',
            'body' => $body,
            'size' => 0,
        ];
 
        $prepared = new ElasticSearch\Prepared\Search();
        $prepared->query($query);

        $response = $this->es->request($prepared);

        $total = array_sum(array_map(function ($bucket) {
            return $this->sumInteractionScores($bucket);
        }, $response['aggregations']['owners']['buckets']));
        foreach ($response['aggregations']['owners']['buckets'] as $bucket) {
            $count = $this->sumInteractionScores($bucket);
            if (!$count) {
                continue;
            }
            $score = [
                'user_guid' => $bucket['key'],
                'total' => $total,
                'count' => $count,
                'sharePct' => $count / $total,
            ];
            yield $score;
        }
    }

    /**
     * Returns if a post is Minds+ paywalled or not
     * @param PaywallEntityInterface $entity
     * @return bool
     */
    public function isPlusEntity(PaywallEntityInterface $entity): bool
    {
        if (!$entity->isPayWall()) {
            return false;
        }
        $threshold = $entity->getWireThreshold();
        return $threshold['support_tier']['urn'] === $this->getPlusSupportTierUrn();
    }

    /**
     * Returns the score of owner bucket interactions
     * @param array $bucket
     * @return int
     */
    private function sumInteractionScores(array $bucket): int
    {
        return array_sum(array_map(function ($bucket) {
            return $bucket['unique_user_actions']['value'] * ContributionValues::metricKeyToMultiplier($bucket['key']);
        }, $bucket['actions']['buckets']));
    }
}
