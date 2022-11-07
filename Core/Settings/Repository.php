<?php

namespace Minds\Core\Settings;

use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Di\Di;
use Minds\Core\Settings\Exceptions\UserSettingsNotFoundException;
use Minds\Core\Settings\Models\UserSettings;
use Minds\Exceptions\ServerErrorException;
use PDO;

class Repository
{
    private PDO $mysqlClientReader;
    private PDO $mysqlClientWriter;

    /**
     * @param MySQLClient|null $mysqlHandler
     * @throws ServerErrorException
     */
    public function __construct(
        private ?MySQLClient $mysqlHandler = null,
    ) {
        $this->mysqlHandler ??= Di::_()->get("Database\MySQL\Client");
        $this->mysqlClientReader = $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_REPLICA);
        $this->mysqlClientWriter = $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_MASTER);
    }

    public function storeUserSettings(UserSettings $settings): bool
    {
        return true;
    }

    public function getUserSettings(string $userGuid): UserSettings
    {
        $query = "SELECT * FROM user_configurations WHERE user_guid = :user_guid";
        $values = [$userGuid];

        $statement = $this->mysqlClientReader->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        if (!$statement->execute()) {
            throw new ServerErrorException($statement->errorInfo());
        }

        if ($statement->rowCount() === 0) {
            throw new UserSettingsNotFoundException();
        }

        return UserSettings::fromData($statement->fetch(PDO::FETCH_ASSOC));
    }
}
