<?php

namespace Minds\Core\Nostr;

use Minds\Core\Data\MySQL;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PDO;
use PDOStatement;

class RelationalRepository
{
    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?MySQL\Client $mysqlClient = null,
    ) {
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->mysqlClient ??= Di::_()->get('Database\MySQL\Client');
    }

    // /**
    //  * Begins MySQL transaction
    //  * @return bool
    //  */
    // public function beginTransaction(): bool
    // {
    //     $dbh = $this->mysqlClient->getConnection(MySQL\Client::CONNECTION_MASTER);
    //     return $dbh->beginTransaction();
    // }

    // /**
    //  * Commits MySQL transactions
    //  * @return bool
    //  */
    // public function commit(): bool
    // {
    //     $dbh = $this->mysqlClient->getConnection(MySQL\Client::CONNECTION_MASTER);
    //     return $dbh->commit();
    // }

    // /**
    //  * Roll back MySQL transactions
    //  * @return bool
    //  */
    // public function rollBack(): bool
    // {
    //     $dbh = $this->mysqlClient->getConnection(MySQL\Client::CONNECTION_MASTER);
    //     return $dbh->rollBack();
    // }

    /**
     * Adds Comment to a relational database
     * @param Comment $comment
     * @return bool
     */
    public function addComment(Comment $comment): bool
    {
        $statement = "INSERT nostr_events 
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
            null, // TOOD enabled
            null, // TODO group conversation
            null, // TODO access id
            null // TODO time created
        ];

        $prepared = $this->mysqlClient->getConnection(MySQL\Client::CONNECTION_MASTER)->prepare($statement);
        return $prepared->execute($values);
    }
}