<?php

namespace Minds\Core\Comments;

use Exception;
use Minds\Core\Di\Di;

use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Data\ElasticSearch\Prepared\Update as PreparedUpdate;
use Minds\Core\Data\ElasticSearch\Prepared\Delete as PreparedDelete;

use Minds\Core\Log\Logger;

class SearchRepository
{
    public function __construct(
        private ?Client $client = null,
        private ?Logger $logger = null
    ) {
        $this->client ??= Di::_()->get('Database\ElasticSearch');
        $this->logger = Di::_()->get('Logger');
    }

    /**
     * Deletes Comment from Elasticsearch
     * @param string $guid
     * @return bool
     */
    public function delete(string $guid)
    {
        try {
            $this->logger->info('Preparing Elasticsearch delete query');

            $query = $this->prepareDelete($guid);
            $response = $this->client->request($query);

            $this->logger->info('Elasticsearch query finished.');
            return true;
        } catch (Exception $e) {
            // If the comment was already deleted, we ack the delete
            if ($e instanceof \OpenSearch\Common\Exceptions\Missing404Exception) {
                $this->logger->info("Elasticsearch returned a 404, comment already deleted.");
                return true;
            }

            $this->logger->error("Elasticsearch query failed $e");
            return false;
        }
    }

    /**
     * Adds Comment to Elasticsearch
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
        int $depth = 0
    ): bool {
        try {
            $this->logger->info('Preparing Elasticsearch update query');

            $query = $this->prepareUpdate($comment, $timeCreated, $timeUpdated, $parentGuid, $depth);
            $response = $this->client->request($query);

            $result = $response['result'];
            $successful = $result === 'created' || $result === 'updated';

            $this->logger->info("Elasticsearch query finished with status: $successful");
            return $successful;
        } catch (Exception $e) {
            $this->logger->error("Elasticsearch query failed $e");
            return false;
        }
    }

    /**
     * Delete Comment from Elasticsearch
     * @param string $guid
     * @return PreparedDelete
     */
    private function prepareDelete(string $guid): PreparedDelete
    {
        $query = [
            'index' => 'minds-comments',
            'type' => '_doc',
            'id' => $guid,
        ];
        $delete = new PreparedDelete();
        $delete->query($query);
        return $delete;
    }

    /**
     * Prepare ES update request
     * @param Comment $comment
     * @param string $timeCreated
     * @param string $timeUpdated
     * @param string $parentGuid
     * @param int $depth
     * @return PreparedUpdate
     */
    private function prepareUpdate(
        Comment $comment,
        string $timeCreated,
        string $timeUpdated,
        ?string $parentGuid = null,
        int $depth = 0
    ): PreparedUpdate {
        $query = [
            'index' => 'minds-comments',
            'type' => '_doc',
            'id' => $comment->getGuid(),
            'body' => [
                'doc' => [
                    'guid' => $comment->getGuid(),
                    'entity_guid' => $comment->getEntityGuid(),
                    'owner_guid' => $comment->getOwnerGuid(),
                    'parent_guid' => $parentGuid ?? -1,
                    'parent_depth' => $depth,
                    'body' => $comment->getBody(),
                    'attachments' => json_encode($comment->getAttachments()),
                    'mature' => (bool) $comment->isMature(),
                    'edited' => (bool) $comment->isEdited(),
                    'spam' => (bool) $comment->isSpam(),
                    'deleted' => (bool) $comment->isDeleted(),
                    'enabled' => true,
                    'group_conversation' => (bool) $comment->isGroupConversation(),
                    'access_id' => $comment->getAccessId(),
                    'updated_at' => $timeUpdated,
                    '@timestamp' => $timeCreated
                ],
                'doc_as_upsert' => true,
            ],
        ];
        $update = new PreparedUpdate();
        $update->query($query);
        return $update;
    }
}
