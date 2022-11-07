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
        $query = "INSERT INTO
            user_configurations (user_guid, terms_accepted_at, supermind_cash_min, supermind_offchain_tokens_min)
            VALUES (:user_guid, :terms_accepted_at, :supermind_cash_min, :supermind_offchain_tokens_min)
            ON DUPLICATE KEY UPDATE
                terms_accepted_at = :terms_accepted_at,
                supermind_cash_min = :supermind_cash_min,
                supermind_offchain_tokens_min = :supermind_offchain_tokens_min,
                updated_at = :updated_at
            ";
        $values = [
            'user_guid' => $settings->getUserGuid(),
            'terms_accepted_at' => $settings->getTermsAcceptedAt() ? date('c', $settings->getTermsAcceptedAt()) : null,
            'supermind_cash_min' => $settings->getSupermindCashMin(),
            'supermind_offchain_tokens_min' => $settings->getSupermindOffchainTokensMin(),
            'updated_at' => date('c', time())
        ];

        $statement = $this->mysqlClientWriter->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);
        return $statement->execute();
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

        return UserSettings::fromData($statement->fetch(PDO::FETCH_ASSOC));
    }
}
