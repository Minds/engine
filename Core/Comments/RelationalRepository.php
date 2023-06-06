<?php

namespace Minds\Core\Comments;

use Minds\Core\Data\MySQL;
use Minds\Core\Di\Di;
use PDO;
use PDOException;

use Minds\Core\Data\MySQL\Client as MySQLClient;
use Selective\Database\Connection;
use Selective\Database\RawExp;

class RelationalRepository
{
    private PDO $mysqlClientWriter;

    public function __construct(
        private ?MySQL\Client $mysqlClient = null,
        private ?Connection $mysqlClientWriterHandler = null,
        private ?\Minds\Core\Log\Logger $logger = null
    ) {
        $this->mysqlClient ??= Di::_()->get("Database\MySQL\Client");
        $this->mysqlClientWriter = $this->mysqlClient->getConnection(MySQLClient::CONNECTION_MASTER);
        $this->mysqlClientWriterHandler ??= new Connection($this->mysqlClientWriter);

        $this->logger = Di::_()->get('Logger');
    }

    /**
     * Delete Comment from a relational database
     * @param string $guid
     * @return bool
    */
    public function delete(string $guid): bool
    {
        $this->logger->info("Preparing DELETE query");

        $statement = $this->mysqlClientWriterHandler->delete()
            ->from('minds_comments')
            ->where('guid', '=', new RawExp(':guid'))
            ->prepare();

        $this->logger->info("Finished preparing DELETE query", [$statement->queryString]);

        $values = ['guid' => $guid];

        $this->mysqlClient->bindValuesToPreparedStatement($statement, $values);

        try {
            $this->logger->info("Executing DELETE query.");
            $statement->execute();
            return $statement->closeCursor();
        } catch (PDOException $e) {
            $this->logger->error("Query error details: ", $statement->errorInfo());
            return false;
        }
    }

    /**
     * Adds Comment to a relational database
     * @param Comment $comment
     * @param string $timeCreated
     * @param string $timeUpdated
     * @param string $parentGuid
     * @param int $depth
     * @return bool
     */
    public function add(
        Comment $comment,
        string $timeCreated,
        string $timeUpdated,
        ?string $parentGuid = null,
        int $depth= 0
    ): bool {

        $statement = $this->mysqlClientWriterHandler->insert()
        ->into('minds_comments')
        ->set([
            'guid' => new RawExp(':guid'),
            'entity_guid' => new RawExp(':entity_guid'),
            'owner_guid' => new RawExp(':owner_guid'),
            'parent_guid' => new RawExp(':parent_guid'),
            'parent_depth' => new RawExp(':parent_depth'),
            'body' => new RawExp(':body'),
            'attachments' => new RawExp(':attachments'),
            'mature' => new RawExp(":mature"),
            'edited' => new RawExp(':edited'),
            'spam' => new RawExp(':spam'),
            'deleted' => new RawExp(':deleted'),
            'enabled' => new RawExp(':is_enabled'),
            'group_conversation' => new RawExp(':group_conversation'),
            'access_id' => new RawExp(':access_id'),
            'time_created' => new RawExp(':time_created'),
            'time_updated' => new RawExp(':time_updated')
        ])
        ->onDuplicateKeyUpdate([
            'body' => new RawExp(':body'),
            'attachments' => new RawExp(':attachments'),
            'mature' => new RawExp(":mature"),
            'edited' => new RawExp(':edited'),
            'spam' => new RawExp(':spam'),
            'deleted' => new RawExp(':deleted'),
            'enabled' => new RawExp(':is_enabled'),
            'access_id' => new RawExp(':access_id'),
            'time_updated' => $timeUpdated
        ])
        ->prepare();

        $values = [
            'guid' => $comment->getGuid(),
            'entity_guid' => $comment->getEntityGuid(),
            'owner_guid' => $comment->getOwnerGuid(),
            'parent_guid' => $parentGuid,
            'parent_depth' => $depth,
            'body' => $comment->getBody(),
            'attachments' => json_encode($comment->getAttachments()),
            'mature' => (bool) $comment->isMature(),
            'edited' => (bool) $comment->isEdited(),
            'spam' => (bool) $comment->isSpam(),
            'deleted' => (bool) $comment->isDeleted(),
            'is_enabled' => true,
            'group_conversation' => (bool) $comment->isGroupConversation(),
            'access_id' => $comment->getAccessId(),
            'time_created' => $timeCreated,
            'time_updated' => $timeUpdated
        ];

        $this->mysqlClient->bindValuesToPreparedStatement($statement, $values);

        try {
            $statement->execute();
            return $statement->closeCursor();
        } catch (PDOException $e) {
            $this->logger->error("Query error details: ", $statement->errorInfo());
            return false;
        }
    }
}
