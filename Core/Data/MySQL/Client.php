<?php
namespace Minds\Core\Data\MySQL;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use PDO;

/**
 * Client that allows access to MySQL
 */
class Client
{
    /** @var PDO */
    protected $pdo;

    public function __construct(protected ?Config $config = null)
    {
        $this->config ??= Di::_()->get('Config');
    }

    /**
     * Returns the PDO interface that enabled access to MySQ:
     * @return PDO
     */
    public function getPDO(): PDO
    {
        if (!$this->pdo) {
            $host = 'mysql';
            $db = 'minds';
            $charset = 'utf8mb4';
            $user = 'root';
            $pass = 'password';
            $options = [];
            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        }
        return $this->pdo;
    }
}
