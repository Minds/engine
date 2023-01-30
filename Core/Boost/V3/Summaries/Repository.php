<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Summaries;

use DateTime;
use Minds\Core\Data\MySQL\Client;

class Repository
{
    public function __construct(private ?Client $mysqlClient = null)
    {
        $this->mysqlClient ??= new Client();
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
}
