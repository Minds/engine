<?php

namespace Minds\Core\Comments;

use Minds\Core\Data\MySQL\AbstractRepository;
use PDO;
use PDOException;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class RelationalRepository extends AbstractRepository
{
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

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

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
            'time_updated' => new RawExp(':time_updated'),
            'source' => new RawExp(':source'),
            'canonical_url' => new RawExp(':canonical_url'),
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
            'time_updated' => $timeUpdated,
            'source' => $comment->getSource()->value,
            'canonical_url' => $comment->getCanonicalUrl(),
        ];

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        try {
            $statement->execute();
            return $statement->closeCursor();
        } catch (PDOException $e) {
            $this->logger->error("Query error details: ", $statement->errorInfo());
            return false;
        }
    }

    /**
     * Returns a comment from their GUID
     *
     * Note: this is incomplete until all the migration is moved over
     */
    public function getByGuid(int $guid): ?Comment
    {
        $query = $this->mysqlClientReaderHandler->select()
            ->columns([
                'guid',
                'entity_guid',
                'owner_guid',
                'parent_guid',
                'parent_depth',
                'body',
                'attachments',
                'mature',
                'edited',
                'spam',
                'deleted',
                'enabled',
                'group_conversation',
                'access_id',
                'time_created',
                'time_updated'
            ])
            ->from('minds_comments')
            ->where('guid', Operator::EQ, new RawExp(':guid'));

        $stmt = $query->prepare();
        $stmt->execute([
            'guid' => $guid,
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return null;
        }

        $row = $rows[0];

        $comment = new Comment();
        $comment
            ->setEntityGuid($row['entity_guid'])
            ->setGuid($row['guid'])
            ->setParentGuid($row['parent_guid'])
            ->setOwnerGuid($row['owner_guid'])
            ->setTimeCreated($row['time_created'])
            ->setTimeUpdated($row['time_updated'])
            ->setBody($row['body'])
            ->setAttachments($row['attachments'] ? json_decode($row['attachments']) : [])
            ->setMature(!!$row['mature'])
            ->setEdited(!!$row['edited'])
            ->setSpam(!!$row['spam'])
            ->setDeleted(!!$row['deleted'])
            ->setEphemeral(false)
            ->markAllAsPristine();

        // TODO: get reply counts in!

        return $comment;
    }
}
