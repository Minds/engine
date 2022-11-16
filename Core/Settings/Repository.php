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
        [
            "fields" => $fields,
            "valueIds" => $valueIds,
            "values" => $values,
            "update" => $updateProperty
        ] = $this->buildUpsertStatementSections($settings);

        $query = "INSERT INTO user_configurations (" . join(',', $fields) . ")
                  VALUES (" . join(',', $valueIds) . ")
                  ON DUPLICATE KEY UPDATE " . join(',', $updateProperty) . ",
                    updated_at = :updated_at";

        $values['updated_at'] = date('c', time());

        $statement = $this->mysqlClientWriter->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);
        return $statement->execute();
    }

    private function buildUpsertStatementSections(UserSettings $settings): array
    {
        $sections = [];

        foreach ($settings->getUpdatedProperties() as $propertyName => $propertyValue) {
            $sections['fields'][] = $propertyName;
            $sections['values'][$propertyName] = $propertyValue;
            $sections['valueIds'][] = ":$propertyName";
            $sections['update'][] = "$propertyName = :$propertyName";
        }

        return $sections;
    }

    /**
     * @param string $userGuid
     * @return UserSettings
     * @throws ServerErrorException
     * @throws UserSettingsNotFoundException
     */
    public function getUserSettings(string $userGuid): UserSettings
    {
        $query = "SELECT * FROM user_configurations WHERE user_guid = :user_guid";
        $values = ['user_guid' => $userGuid];

        $statement = $this->mysqlClientReader->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        if (!$statement->execute()) {
            throw new ServerErrorException($statement->errorInfo());
        }

        if ($statement->rowCount() === 0) {
            throw new UserSettingsNotFoundException();
        }

        return (new UserSettings())
            ->withData($statement->fetch(PDO::FETCH_ASSOC));
    }
}
