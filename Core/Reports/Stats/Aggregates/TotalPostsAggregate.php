<?php
namespace Minds\Core\Reports\Stats\Aggregates;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Data\ElasticSearch\Prepared;

class TotalPostsAggregate implements ModerationStatsAggregateInterface
{
    /** @var Client $es */
    private $es;

    /** @var Config */
    protected $config;

    public function __construct($es = null, Config $config = null)
    {
        $this->es = $es ?: Di::_()->get('Database\ElasticSearch');
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * @return init
     */
    public function get(): int
    {
        $body = [
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'range' => [
                                '@timestamp' => [
                                    'gte' => strtotime('midnight -30 days') * 1000,
                                    'lte' => time() * 1000,
                                    'format' => 'epoch_millis',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $query = [
            'index' => $this->config->get('elasticsearch')['indexes']['search_prefix'] . '-activity',
            'body' => $body,
            'size' => 0,
        ];

        $prepared = new Prepared\Search();
        $prepared->query($query);
        $result = $this->es->request($prepared);

        return $result['hits']['total']['value'];
    }
}
