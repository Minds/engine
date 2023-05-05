<?php

namespace Minds\Core\Comments;

use Minds\Core\Data\MySQL;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PDO;
use PDOStatement;

use Minds\Core\Data\MySQL\Client as MySQLClient;
use Selective\Database\Connection;
use Selective\Database\RawExp;

use Minds\Core\Log\Logger;
use DateTimeImmutable;


class RelationalRepository
{
    private PDO $mysqlClientReader;
    private PDO $mysqlClientWriter;
    private Connection $mysqlClientWriterHandler;
    private Connection $mysqlClientReaderHandler;

    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?MySQL\Client $mysqlClient = null,
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
     * @return bool
     */
    public function addComment(Comment $comment): bool
    {
        $statement = "INSERT minds_comments
        (
            guid,
            entity_guid,
            owner_guid,
            container_guid,
            parent_guid,
            parent_depth,
            body,
            attachments,
            mature,
            edited,
            spam,
            deleted,
            `enabled`,
            group_conversation,
            access_id,
            time_created
        )
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE id=id";

        $values = [
            $comment->getGuid(),
            $comment->getEntityGuid(),
            $comment->getOwnerGuid(),
            null, // TODO container guid
            null, // TODO parent guid
            null, // TODO parent depth
            $comment->getBody(),
            json_encode($comment->getAttachments()),
            $comment->isMature(),
            $comment->isEdited(),
            $comment->isSpam(),
            $comment->isDeleted(),
            true, // TODO enabled
            $comment->isGroupConversation(),
            $comment->getAccessId(),
            $comment->getTimeCreated()
        ];

        $prepared = $this->mysqlClient->getConnection(MySQL\Client::CONNECTION_MASTER)->prepare($statement);
        return $prepared->execute($values);
    }

    /**
     * Adds Comment to a relational database
     * @param Comment $comment
     * @return bool
     */
    public function add(Comment $comment): bool
    {
        $this->logger->addInfo("Preparing insert query");

        $statement = $this->mysqlClientWriterHandler->insert()
        ->into('minds_comments')
        ->set([
            'guid' => new RawExp(':guid'),
            'entity_guid' => new RawExp(':entity_guid'),
            'owner_guid' => new RawExp(':owner_guid'),
            'container_guid' => new RawExp(':container_guid'),
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
        ->prepare();

        $this->logger->addInfo("Finished preparing insert query", [$statement->queryString]);

        // Set date
        $date = date('Y-m-d H:i:s', $comment->getTimeCreated());

        // Set Parent GUID
        $parentGuid = null;
        if ($comment->getParentGuidL2() > 0) {
            $parentGuid = $comment->getParentGuidL2();
        } else if ($comment->getParentGuidL1() > 0) {
            $parentGuid = $comment->getParentGuidL1();
        }

        $values = [
            'guid' => $comment->getGuid(),
            'entity_guid' => $comment->getEntityGuid(),
            'owner_guid' => $comment->getOwnerGuid(),
            'container_guid' => null, // TODO container gui,
            'parent_guid' => $parentGuid,
            'parent_depth' => null, // TODO parent depth,
            'body' => $comment->getBody(),
            'attachments' => json_encode($comment->getAttachments()),
            'mature' => !!$comment->isMature(),
            'edited' => !!$comment->isEdited(),
            'spam' => !!$comment->isSpam(),
            'deleted' => !!$comment->isDeleted(),
            'is_enabled' => true, // TODO enable,
            'group_conversation' => !!$comment->isGroupConversation(),
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