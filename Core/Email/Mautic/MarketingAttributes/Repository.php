<?php
namespace Minds\Core\Email\Mautic\MarketingAttributes;

use Minds\Core\Data\MySQL\Client;
use PDO;

class Repository
{
    public function __construct(
        protected ?Client $mysqlClient = null
    ) {
        $this->mysqlClient ??= new Client();
    }

    /**
     * @param string $userGuid
     * @param string $attributeName
     * @param string $attributeValues
     * @return bool
     */
    public function add(string $userGuid, string $attributeName, string $attributeValue): bool
    {
        $statement = "INSERT INTO users_marketing_attributes (user_guid, attribute_key, attribute_value) 
            VALUES (:user_guid, :attribute_key, :attribute_value)
            ON DUPLICATE KEY UPDATE 
                updated_timestamp=IF(attribute_value = :attribute_value, updated_timestamp, CURRENT_TIMESTAMP()),
                attribute_value=:attribute_value";
        $values = [
            'user_guid' => $userGuid,
            'attribute_key' => $attributeName,
            'attribute_value' => $attributeValue,
        ];

        $stmt = $this->mysqlClient->getConnection(Client::CONNECTION_MASTER)->prepare($statement);
        return $stmt->execute($values);
    }

    /**
     * @param int $fromTs (optional)
     * @return iterable
     */
    public function getList(int $fromTs = null): iterable
    {
        $statement = "SELECT
            a.user_guid,
            {$this->getColumnsStatement()}
        FROM users_marketing_attributes a
        LEFT JOIN (
            SELECT user_guid, MAX(updated_timestamp) as last_updated_timestamp
            FROM users_marketing_attributes
            GROUP BY 1
        ) b ON a.user_guid = b.user_guid  
        ";
        
        $values = [];

        if ($fromTs) {
            $statement .= " WHERE b.last_updated_timestamp > :from_ts ";
            $values['from_ts'] = date('c', $fromTs);
        }

        $statement .= "GROUP BY a.user_guid";

        $stmt = $this->mysqlClient->getConnection(Client::CONNECTION_REPLICA)->prepare($statement);
        $stmt->execute($values);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            yield $row;
        }
    }

    /**
     * Returns all the attribute names in the table
     */
    private function getColumnsStatement(): string
    {
        $statement = "SELECT DISTINCT attribute_key FROM users_marketing_attributes";
        $stmt = $this->mysqlClient->getConnection(Client::CONNECTION_REPLICA)->prepare($statement);
        $stmt->execute();

        $columns = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $columns[] = "MAX(CASE WHEN attribute_key='{$row['attribute_key']}' THEN attribute_value END) as {$row['attribute_key']}";
        }

        return implode(',', $columns);
    }
}
