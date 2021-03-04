<?php
/**
 * A simple service to find a block from a timestamp
 */
namespace Minds\Core\Blockchain\Services;

use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Di\Di;
use Minds\Core\Data\Cassandra\Prepared;
use Minds\Core\Data\Cassandra\Client as CassandraClient;
use Cassandra;

class BlockFinder
{
    /** @var Etherscan */
    protected $etherscan;

    /** @var CassandraClient */
    protected $cassandra;

    /**
     * @param Etherscan $etherscan
     * @param CassandraClient $cassandra
     */
    public function __construct(Etherscan $etherscan = null, $cassandra = null)
    {
        $this->etherscan = $etherscan ?? new Etherscan();
        $this->cassandra = $cassandra ?? Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * Returns the closest block number to the provided timestamp
     * @param int $unixTimestamp
     * @return int
     */
    public function getBlockByTimestamp(int $unixTimestamp, $useCache = true): int
    {
        if ($useCache && $blockNumber = $this->getFromCache($unixTimestamp)) {
            return (int) $blockNumber;
        }

        $blockNumber = $this->etherscan->getBlockNumberByTimestamp($unixTimestamp);

        $this->addToCache($unixTimestamp, $blockNumber);

        return (int) $blockNumber;
    }

    /**
     * Returns a block number within 5 minutes
     * @param int $unixTimestamp
     * @return int
     */
    private function getFromCache(int $unixTimestamp): ?int
    {
        $statement = "SELECT * FROM eth_blocks 
            WHERE date = ?
            AND timestamp <= ?
            AND timestamp > ?
            LIMIT 1";

        $values = [
            new Cassandra\Date($unixTimestamp),
            new Cassandra\Timestamp($unixTimestamp, 0),
            new Cassandra\Timestamp($unixTimestamp - 300, 0), // Only allow to deviate 5 minutes from block
        ];


        $prepared = new Prepared\Custom();
        $prepared->query($statement, $values);

        $rows = $this->cassandra->request($prepared);

        if ($rows && $rows[0]) {
            return (int) $rows[0]['block_number'];
        }

        return null;
    }

    /**
     * @param int $unixTimestamp
     * @param int $blockNumber
     * @return void
     */
    private function addToCache(int $unixTimestamp, int $blockNumber): void
    {
        $statement = "INSERT INTO eth_blocks (date, timestamp, block_number) VALUES (?,?,?)";
        $values = [
            new Cassandra\Date($unixTimestamp),
            new Cassandra\Timestamp($unixTimestamp, 0),
            $blockNumber,
        ];

        $prepared = new Prepared\Custom();
        $prepared->query($statement, $values);

        $this->cassandra->request($prepared);
    }
}
