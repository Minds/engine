<?php

namespace Minds\Controllers\Cli;

use Minds\Core;
use Minds\Cli;
use Minds\Interfaces;
use PDO;

class MySQL extends Cli\Controller implements Interfaces\CliControllerInterface
{
    public function __construct()
    {
    }

    public function help($command = null)
    {
        $this->out('TBD');
    }
    
    public function exec()
    {
        $mysqlClient = Core\Di\Di::_()->get('Database\MySQL\Client');
        $pdo = $mysqlClient->getConnection();
        $statement = $pdo->query("SHOW TABLES;");
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        var_dump($row);
    }

    public function prune()
    {
        // Seen entities
        $mysqlClient = Core\Di\Di::_()->get('Database\MySQL\Client');
        $pdo = $mysqlClient->getConnection();
        $statement = $pdo->prepare("DELETE FROM pseudo_seen_entities WHERE last_seen_timestamp < :timestamp");
        $result = $statement->execute([ 'timestamp' => date('c', strtotime('21 days ago'))]);
        var_dump($result);
    }
}
