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
}
