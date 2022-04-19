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
 * Must be configured with BigQuery project ID in settings.php
 * and credentials pathed to via an environmental variable.
 *
 * See https://cloud.google.com/bigquery/docs/quickstarts/quickstart-client-libraries#client-libraries-usage-php
 * for additional setup information.
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

        if (!$projectId = $this->config->get('google')['bigquery']['project_id'] ?? false) {
            $this->logger->error('BigQuery is not properly configured');
        }

        $this->client ??= new BigQueryClient([
            'projectId' => $projectId
        ]);
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
}
