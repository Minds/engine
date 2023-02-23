<?php
namespace Minds\Core\Security\Block\Repositories;

use Minds\Common\Repository\Response;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Core\Security\Block\BlockEntry;
use Minds\Core\Security\Block\BlockListOpts;
use PDO;

class VitessRepository implements RepositoryInterface
{
    private PDO $mysqlClientReader;
    private PDO $mysqlClientWriter;

    /**
     * @param MySQLClient|null $mysqlHandler
     * @param EntitiesBuilder|null $entitiesBuilder
     * @throws ServerErrorException
     */
    public function __construct(
        private ?MySQLClient $mysqlHandler = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Logger $logger = null
    ) {
        $this->mysqlHandler ??= Di::_()->get("Database\MySQL\Client");
        $this->mysqlClientReader = $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_REPLICA);
        $this->mysqlClientWriter = $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_MASTER);

        $this->entitiesBuilder ??= Di::_()->get("EntitiesBuilder");
        $this->logger ??= Di::_()->get('Logger');
    }

    /**
     * Adds a block to the database
     * @param Block $block
     * @return bool
     */
    public function add(BlockEntry $block): bool
    {
        $query = "INSERT INTO blocked_users (user_guid, blocked_guid)
            VALUES (:user_guid, :blocked_guid)";

        $values = [
            'user_guid' => $block->getActorGuid(),
            'blocked_guid' => $block->getSubjectGuid(),
        ];

        $statement = $this->mysqlClientWriter->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);
        
        try {
            return $statement->execute();
        } catch (\Exception $e) {
            $this->logger->error($e);
            return false;
        }
    }

    /**
     * Removes a block to the database
     * @param Block $block
     * @return bool
     */
    public function delete(BlockEntry $block): bool
    {
        $query = "DELETE FROM blocked_users WHERE user_guid = :user_guid AND blocked_guid = :blocked_guid";

        $values = [
            'user_guid' => $block->getActorGuid(),
            'blocked_guid' => $block->getSubjectGuid(),
        ];

        $statement = $this->mysqlClientWriter->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        try {
            return $statement->execute();
        } catch (\Exception $e) {
            $this->logger->error($e);
            return false;
        }
    }

    /**
     * Get a single block entry
     * @param string $userGuid - user guid to get entry for.
     * @param string $blockedGuid - guid of blocked user.
     * @return BlockEntry
     */
    public function get(string $userGuid, string $blockedGuid): ?BlockEntry
    {
        $query = "SELECT * FROM blocked_users WHERE user_guid = :user_guid AND blocked_guid = :blocked_guid";
        $values = [
            'user_guid' => $userGuid,
            'blocked_guid' => $blockedGuid
        ];

        $statement = $this->mysqlClientReader->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();
        $blockData = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$blockData) {
            return null;
        }

        return (new BlockEntry)
            ->setActorGuid($blockData['user_guid'])
            ->setSubjectGuid($blockData['blocked_guid']);
    }

    /**
     * Return a list of blocked users
     * @return Response
     */
    public function getList(BlockListOpts $opts): Response
    {
        $query = "SELECT * FROM blocked_users WHERE user_guid = :user_guid LIMIT :offset, :limit";
        $values = [
            'user_guid' => $opts->getUserGuid(),
            'limit' => $opts->getLimit(),
            'offset' => $opts->getPagingToken() ?: 0
        ];

        $statement = $this->mysqlClientReader->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();
        $response = (new Response())
            ->setPagingToken($statement->rowCount())
            ->setLastPage($statement->rowCount() !== $opts->getLimit());

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $blockData) {
            $response[] = (new BlockEntry)
                ->setActorGuid($blockData['user_guid'])
                ->setSubjectGuid($blockData['blocked_guid']);
        }
        return $response;
    }

    /**
     * Count blocks
     * @param string $userGuid
     * @return int
     */
    public function countList(string $userGuid): int
    {
        $query = "SELECT count(*) FROM blocked_users WHERE user_guid = :user_guid";
        $values = [
            'user_guid' => $userGuid
        ];

        $statement = $this->mysqlClientReader->prepare($query);
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();
        return $statement->fetch(PDO::FETCH_ASSOC);
    }
}
