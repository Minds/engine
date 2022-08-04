<?php
namespace Minds\Core\Data\MySQL;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOStatement;

/**
 * Client that allows access to MySQL
 */
class Client
{
    /** @var string */
    const CONNECTION_MASTER = 'master';

    /** @var string */
    const CONNECTION_REPLICA = 'replica';

    /** @var string */
    const CONNECTION_RDONLY = 'rdonly';

    /** @var PDO[] */
    protected array $connections = [];

    public function __construct(protected ?Config $config = null)
    {
        $this->config ??= Di::_()->get('Config');
    }

    /**
     * Returns the PDO interface that enabled access to MySQL
     * By default will only query master nodes.
     * To query replicas pass `connectionType: 'replicas'`
     * @param string $connectionType
     * @return PDO
     * @throws ServerErrorException
     */
    public function getConnection(string $connectionType = 'master'): PDO
    {
        if (!in_array($connectionType, [
            static::CONNECTION_MASTER,
            static::CONNECTION_REPLICA,
            static::CONNECTION_RDONLY,
        ], true)) {
            throw new ServerErrorException("\$connectionType must be one of MATSER, REPLICA or RDONLY. $connectionType provided");
        }

        if (!isset($this->connections[$connectionType])) {
            $config = $this->config->get('mysql') ?? [];
            $host = $config['host'] ?? 'mysql';
            if ($config['is_vitess'] ?? true) {
                $db = ($config['db'] ?? 'minds') . '@' . $connectionType;
            } else {
                $db = ($config['db'] ?? 'minds');
            }
            $charset = 'utf8mb4';
            $user =  $config['user'] ?? 'root';
            $pass = $config['password'] ?? 'password'; // always set via a config variable and never in settings.php
            $options = [ ];
            if ($config['ssl_cert_path'] ?? null) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $config['ssl_cert_path'];
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = !($config['ssl_skip_verify'] ?? false);
            }
            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $this->connections[$connectionType] = new PDO($dsn, $user, $pass, $options);
        }
        return $this->connections[$connectionType];
    }

    /**
     * Method to bind values from an array to a prepared statement with the correct type
     * @param PDOStatement $statement
     * @param array $values
     * @return void
     */
    public function bindValuesToPreparedStatement(PDOStatement $statement, array $values): void
    {
        foreach ($values as $key => $value) {
            $statement->bindValue(
                $key,
                $value,
                $this->getParameterType($value)
            );
        }
    }

    /**
     * Return the correct PDO parameter type for a given value
     * @param int|bool|string|null $value
     * @return int
     */
    private function getParameterType(int|bool|string|null $value): int
    {
        return match (gettype($value)) {
            "boolean" => PDO::PARAM_BOOL,
            "integer" => PDO::PARAM_INT,
            "NULL" => PDO::PARAM_NULL,
            default => PDO::PARAM_STR
        };
    }
}
