<?php

namespace Minds\Core\Comments;

use Minds\Core\Data\MySQL;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use PDO;
use PDOException;

use Minds\Core\Data\MySQL\Client as MySQLClient;
use Selective\Database\Connection;
use Selective\Database\RawExp;

class RelationalRepository
{
    private PDO $mysqlClientWriter;
    private Connection $mysqlClientWriterHandler;

    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?MySQL\Client $mysqlClient = null,
        private ?\Minds\Core\Log\Logger $logger = null
    ) {
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');

        $this->mysqlClient ??= Di::_()->get("Database\MySQL\Client");
        $this->mysqlClientWriter = $this->mysqlClient->getConnection(MySQLClient::CONNECTION_MASTER);
        $this->mysqlClientWriterHandler = new Connection($this->mysqlClientWriter);

        $this->logger = Di::_()->get('Logger');
    }

    /**
     * Adds Comment to a relational database
     * @param Comment $comment
     * @param string $date
     * @param string $parentGuid
     * @param int $depth
     * @return bool
     */
    public function add(
        Comment $comment,
        string $date,
        ?string $parentGuid,
        ?int $depth
    ): bool {
        $this->logger->addInfo("Preparing insert query");

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
        ])
        ->onDuplicateKeyUpdate([
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
            'time_updated' => date('c')
        ])
        ->prepare();

        $this->logger->addInfo("Finished preparing insert query", [$statement->queryString]);

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
            'time_created' => $date
        ];

        $this->mysqlClient->bindValuesToPreparedStatement($statement, $values);

        try {
            $this->logger->addInfo("Executing query.");
            return $statement->execute();
        } catch (PDOException $e) {
            $this->logger->addError("Query error details: ", $statement->errorInfo());
            return false;
        }
    }
}
