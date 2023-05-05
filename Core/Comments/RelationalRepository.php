<?php

namespace Minds\Core\Comments;

use Minds\Core\Data\MySQL;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PDO;
use PDOStatement;

use Selective\Database\Connection;
use Minds\Core\Log\Logger;
use Minds\Core\Data\MySQL\Client as MySQLClient;

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
            'guid' => $comment->getGuid(),
            'entity_guid' => $comment->getEntityGuid(),
            'owner_guid' => $comment->getOwnerGuid(),
            'container_guid' => null, // TODO container gui,
            'parent_guid' => null, // TODO parent gui,
            'parent_depth' => null, // TODO parent dept,
            'body' => $comment->getBody(),
            'attachments' => json_encode($comment->getAttachments()),
            'mature' => !!$comment->isMature(),
            'edited' => !!$comment->isEdited(),
            'spam' => !!$comment->isSpam(),
            'deleted' => !!$comment->isDeleted(),
            'enabled' => true, // TODO enable,
            'group_conversation' => !!$comment->isGroupConversation(),
            'access_id' => $comment->getAccessId(),
            'time_created' => $comment->getTimeCreated(),
        ])
        ->prepare();

        $this->logger->addInfo("Finished preparing insert query", [$statement->queryString]);

        try {
            $statement->execute();
            $this->logger->addInfo("Done.");
        } catch (PDOException $e) {
            $this->logger->addError("Query error details: ", $statement->errorInfo());
            return false;
        }
    }
}