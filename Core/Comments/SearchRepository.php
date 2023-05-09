<?php

namespace Minds\Core\Comments;

use Exception;
use Minds\Core\Di\Di;

use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Data\ElasticSearch\Prepared\Update as PreparedUpdate;

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
     * Adds Comment to Elasticsearch
     * @param Comment $comment
     * @param string $date
     * @param string $parentGuid
     * @param int $depth
     * @return bool
     */
    public function add(
        Comment $comment,
        string $date,
        ?string $parentGuid = null,
        int $depth
    ): bool {
        try {
            $this->logger->info('Preparing Elasticsearch update query');

            $query = $this->prepareQuery($comment, $date, $parentGuid, $depth);
            $response = $this->client->request($query);

            $this->logger->info('Elasticsearch query finished.');
            return true;
        } catch (Exception $e) {
            $this->logger->error("Elasticsearch query failed $e");

            return false;
        }
    }

    /**
     * Prepare ES update request
     * @param Comment $comment
     * @param string $date
     * @param string $parentGuid
     * @param int $depth
     * @return PreparedUpdate
     */
    private function prepareQuery(
        Comment $comment,
        string $date,
        ?string $parentGuid = null,
        int $depth
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
                    'updated_at' => date('c'),
                    '@timestamp' => $date
                ],
                'doc_as_upsert' => true,
            ],
        ];
        $update = new PreparedUpdate();
        $update->query($query);
        return $update;
    }
}
