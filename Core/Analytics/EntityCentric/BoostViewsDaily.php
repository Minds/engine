<?php

namespace Minds\Core\Analytics\EntityCentric;

use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Data\ElasticSearch\Prepared\Search;
use Minds\Core\Di\Di;
use Minds\Helpers\Time;

class BoostViewsDaily
{
    /** @var Client */
    protected $es;

    /** @var array */
    protected $dailyViews = [];

    /** @var int */
    protected $totalViews = 0;

    /** @var int */
    protected $startDayMs;

    /** @var int */
    protected $endDayMs;

    public function __construct(Client $esClient = null)
    {
        $this->es = $esClient ?: Di::_()->get('Database\ElasticSearch');
        $this->lastSevenDays();
    }

    protected function clearData(): void
    {
        $this->dailyViews = [];
        $this->totalViews = 0;
    }

    public function lastSevenDays(): self
    {
        return $this->dateRange(strtotime('yesterday -1 week'), strtotime('yesterday'));
    }

    public function dateRange(int $start, int $end): self
    {
        $this->clearData();
        $this->startDayMs = Time::toInterval($start, Time::ONE_DAY) * 1000;
        $this->endDayMs = Time::toInterval($end, Time::ONE_DAY) * 1000;
        return $this;
    }

    protected function query(): void
    {
        if (!empty($this->dailyViews)) {
            return;
        }

        $prepared = new Search();
        $prepared->query($this->buildQuery());
        $response = $this->es->request($prepared);

        if (isset($response['aggregations']['boost_views_total'])) {
            $this->totalViews = $response['aggregations']['boost_views_total']['value'];
        }

        if (isset($response['aggregations']['boost_views_daily']['buckets'])) {
            foreach ($response['aggregations']['boost_views_daily']['buckets'] as $bucket) {
                $this->dailyViews[$bucket['key']] = $bucket['boost_views']['value'];
            }
        }
    }

    protected function buildQuery(): array
    {
        $must = [
            'range' => [
                '@timestamp' => [
                    'gte' => $this->startDayMs,
                    'lte' => $this->endDayMs,
                ]
            ]
        ];

        $query = [
            'index' => 'minds-entitycentric-*',
            'size' => 0,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => $must,
                    ],
                ],
                'aggs' => [
                    'boost_views_total' => [
                        'sum' => [
                            'field' => 'views::boosted',
                        ],
                    ],
                    'boost_views_daily' => [
                        'date_histogram' => [
                            'field' => '@timestamp',
                            'interval' => '1d'
                        ],
                        'aggs' => [
                            'boost_views' => [
                                'sum' => [
                                    'field' => 'views::boosted'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $query;
    }

    public function getAll(): array
    {
        $this->query();
        return $this->dailyViews;
    }

    public function getTotal(): int
    {
        $this->query();
        return $this->totalViews;
    }

    public function getMax(): int
    {
        $this->query();
        return max($this->dailyViews);
    }

    public function getAvg(): float
    {
        $this->query();
        return !empty($this->dailyViews) ? array_sum($this->dailyViews) / count($this->dailyViews) : 0;
    }
}
