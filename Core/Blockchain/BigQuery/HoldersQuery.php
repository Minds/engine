<?php

namespace Minds\Core\Blockchain\BigQuery;

use Minds\Core\Config\Config;
use Minds\Core\Data\BigQuery\Client;
use Minds\Core\Data\Interfaces\BigQueryInterface;
use Minds\Core\Di\Di;
use Minds\Exceptions\ServerErrorException;

/**
 * Get all token holders using BigQuery.
 */
class HoldersQuery implements BigQueryInterface
{
    /** @var string */
    private $tokenAddress;

    /**
     * Constructor.
     * @param ?Client $client - BigQuery client.
     * @param ?Config $config - Minds config.
     */
    public function __construct(
        private ?Client $client = null,
        private ?Config $config = null
    ) {
        $this->client ??= Di::_()->get('BigQuery');
        $this->config ??= Di::_()->get('Config');

        $this->tokenAddress = $this->config->get('blockchain')['token_address'] ?? '';
    }

    /**
     * Get all token holders
     * @return Iterable - token holders.
     */
    public function get(): Iterable
    {
        if (!$this->tokenAddress) {
            throw new ServerErrorException('Token address is not set');
        }
        return $this->client->query(
            $this->buildQuery()
        )->rows();
    }

    /**
     * Builds query.
     * @return string - built query.
     */
    protected function buildQuery(): string
    {
        return <<<ENDSQL
            SELECT
                Txs.addr,
                SUM(COALESCE(
                    Txs.value,
                    0
                )) / 1e18 as balance
            FROM
                (
                    SELECT
                        to_address as addr,
                        SUM(COALESCE(CAST(value AS numeric), 0)) as value
                    FROM
                        `bigquery-public-data.crypto_ethereum.token_transfers`
                    WHERE
                        token_address = LOWER('$this->tokenAddress')
                    GROUP BY 1
                    union all 
                    SELECT
                        from_address as addr,
                        -SUM(COALESCE(CAST(value AS numeric), 0)) as value
                    FROM
                        `bigquery-public-data.crypto_ethereum.token_transfers`
                    WHERE
                        token_address = LOWER('$this->tokenAddress')
                    GROUP BY 1
                ) as Txs
            GROUP BY
                Txs.addr
        ENDSQL;
    }
}
