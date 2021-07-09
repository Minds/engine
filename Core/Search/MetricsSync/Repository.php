<?php

namespace Minds\Core\Search\MetricsSync;

use Minds\Core\Data\ElasticSearch\Client as ElasticsearchClient;
use Minds\Core\Data\ElasticSearch\Prepared;
use Minds\Core\Di\Di;
use Minds\Core\Features\Manager as Features;
use Minds\Core\Search\SortingAlgorithms;
use Minds\Helpers\Text;

class Repository
{
    /** @var ElasticsearchClient */
    protected $client;

    /** @var string */
    protected $index;

    /** @var array $pendingBulkInserts * */
    private $pendingBulkInserts = [];

    public function __construct($client = null, $config = null)
    {
        $this->client = $client ?: Di::_()->get('Database\ElasticSearch');
        $config = $config ?: Di::_()->get('Config');
        $this->index = $config->get('elasticsearch')['indexes']['search_prefix'];
    }

    /**
     * Add metrics sync to the record
     * @param MetricsSync $metric
     * @return bool
     */
    public function add(MetricsSync $metric): bool
    {
        $key = $metric->getMetric();

        if ($metric->getPeriod()) {
            $key .= ":{$metric->getPeriod()}";
        }

        $body = [
            $key => $metric->getCount(),
            "{$key}:synced" => $metric->getSynced()
        ];

        $this->pendingBulkInserts[] = [
            'update' => [
                '_id' => (string) $metric->getGuid(),
                '_index' => "$this->index-{$metric->getType()}"
            ],
        ];

        $this->pendingBulkInserts[] = [
            'doc' => $body,
            'doc_as_upsert' => true,
        ];

        if (count($this->pendingBulkInserts) > 2000) { //1000 inserts
            $this->bulk();
        }

        return true;
    }

    /**
     * Run a bulk insert job (quicker).
     */
    public function bulk(): void
    {
        if (count($this->pendingBulkInserts) > 0) {
            $res = $this->client->bulk(['body' => $this->pendingBulkInserts]);
            $this->pendingBulkInserts = [];
        }
    }
}
