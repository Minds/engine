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
            $config = $this->config->get('mysql') ?? [];
            $host = $config['host'] ?? 'mysql';
            $db = $config['db'] ?? 'minds';
            $charset = 'utf8mb4';
            $user =  $config['user'] ?? 'root';
            $pass = $config['password'] ?? 'password'; // always set via a config variable and never in settings.php
            $options = [];
            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        }
        return $this->pdo;
    }
}
