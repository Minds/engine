<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Summaries;

use DateTime;
use Minds\Core\Data\MySQL\Client;
use PDO;
use PDOException;

class Repository
{
    public function __construct(private ?Client $mysqlClient = null)
    {
        $this->mysqlClient ??= new Client();
    }
    
    /**
     * Start the transaction
     */
    public function beginTransaction(): void
    {
        if ($this->getMasterConnection()->inTransaction()) {
            throw new PDOException("Cannot initiate transaction. Previously initiated transaction still in progress.");
        }

        $this->getMasterConnection()->beginTransaction();
    }

    /**
     * Commit the transaction
     */
    public function commitTransaction(): void
    {
        $this->getMasterConnection()->commit();
    }

    /**
     * @param string $guid
     * @param DateTime $date
     * @param int $views
     * @return bool
     */
    public function add(string $guid, DateTime $date, int $views): bool
    {
        $statement = "INSERT INTO boost_summaries (guid, date, views) VALUES (:guid,:date,:views) ON DUPLICATE KEY UPDATE views=:views";
        $values = [
            'guid' => $guid,
            'date' => $date->format('c'),
            'views' => $views
        ];

        $stmt = $this->mysqlClient->getConnection(Client::CONNECTION_MASTER)->prepare($statement);
        return $stmt->execute($values);
    }

    /**
     * Returns the writer connection
     * @return PDO
     */
    protected function getMasterConnection(): PDO
    {
        return $this->mysqlClient->getConnection(Client::CONNECTION_MASTER);
    }
}
