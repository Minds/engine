<?php

namespace Minds\Core\Data\BigQuery;

use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\BigQuery\QueryResults;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Exceptions\ServerErrorException;

/**
 * BigQuery client - used to get results from BigQuery.
 * Must be configured with BigQuery project ID and path to JSON authentication file in settings.php.
 *
 * See https://cloud.google.com/bigquery/docs/quickstarts/quickstart-client-libraries#client-libraries-usage-php
 * for additional setup details.
 */
class Client
{
    /**
     * Constructor for client.
     * @param ?BigQueryClient $client - BigQuery client.
     * @param ?Config $config - config.
     * @param ?Logger $logger - logger.
     */
    public function __construct(
        private ?BigQueryClient $client = null,
        private ?Config $config = null,
        private ?Logger $logger = null
    ) {
        $this->config ??= Di::_()->get('Config');
        $this->logger ??= Di::_()->get('Logger');
        $this->initBigQuery();
    }

    /**
     * Execute a query against BigQuery.
     * @param string $query - query to execute.
     * @return QueryResults - results of query.
     */
    public function query(string $query): QueryResults
    {
        $queryJobConfig = $this->client->query($query);
        $queryResults = $this->client->runQuery($queryJobConfig);

        if ($queryResults->isComplete()) {
            return $queryResults;
        } else {
            throw new ServerErrorException('The query failed to complete');
        }
    }

    /**
     * Initialize BigQuery client. Requires config to be set or will return
     * without initializing client.
     * @return void
     */
    private function initBigQuery(): void
    {
        $bigQueryConfig = $this->config->get('google')['bigquery'] ?? false;

        if (!$bigQueryConfig || !isset($bigQueryConfig['project_id']) || !isset($bigQueryConfig['key_file_path'])) {
            $this->logger->error('BigQuery is not properly configured');
            return;
        }

        $this->client ??= new BigQueryClient([
            'projectId' => $bigQueryConfig['project_id'],
            'keyFilePath' => $bigQueryConfig['key_file_path'],
        ]);
    }
}
