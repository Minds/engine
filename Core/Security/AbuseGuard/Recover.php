<?php
/**
 * Abuse Guard Recover
 */
namespace Minds\Core\Security\AbuseGuard;

use Minds\Core;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Entities;
use Minds\Helpers;

class Recover
{
    private $accused;

    /** @var Client */
    protected $client;

    /** @var Config */
    protected $config;

    public function __construct($client = null, Config $config = null)
    {
        $this->client = $client ?: Di::_()->get('Database\ElasticSearch');
        $this->config = $config ?? Di::_()->get('Config');
    }

    public function setAccused($accused)
    {
        $this->accused = $accused;
        return $this;
    }

    public function recover()
    {
        $manager = (new Core\Comments\Manager());
        $user = $this->accused->getUser();
        foreach ($this->getComments() as $comment) {
            if ($comment->getGuid() && !$comment->isDeleted()) {
                $comment->setDeleted(true);
                $manager->update($comment);

                //and remove any attachments also
                if ($comment->attachment_guid) {
                    /** @var Entities\Image|Entities\Video */
                    $attachment = Entities\Factory::build($comment->attachment_guid);
                    $attachment->setFlag('deleted', true);
                    $attachment->save();
                }
            }
        }

        foreach ($this->getDownVotes() as $post) {
            if ($post) {
                Helpers\Counters::increment($post->guid, "thumbs:down", -1);
            }
        }

        foreach ($this->getPosts() as $post) {
            if ($post) {
                $post->setDeleted(true);
                $post->access_id = 0;
                $post->save();

                if ($post->entity_guid) {
                    $attachment = Entities\Factory::build($post->entity_guid);
                    $attachment->setFlag('deleted', true);
                    $attachment->save();
                }
            }
        }

        return true;
    }

    /**
     * @return Core\Comments\Comment[]
     */
    private function getComments()
    {
        $query = [
            'index' => 'minds-metrics-*',
            'size' => 1000,
            'body' => [
                'query' => [
                    'bool' => [
                        'should' => [
                            [
                                'term' => [
                                    'entity_type.keyword' => 'comment',
                                    'user_guid.keyword' => $this->accused->getUser()->guid
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $prepared = new Core\Data\ElasticSearch\Prepared\Search();
        $prepared->query($query);

        $result = $this->client->request($prepared);

        $repository = new Core\Comments\Repository();

        $comments = [];
        if ($result) {
            foreach ($result['hits']['hits'] as $row) {
                if (isset($row['_source']['comment_guid'])) {
                    $comments[] = $repository->getByLuidOrGuid($row['_source']['comment_guid']);
                }
            }
        }

        return $comments;
    }

    private function getDownVotes()
    {
        $query = [
            'index' => 'minds-metrics-*',
            'size' => 1000,
            'body' => [
                'query' => [
                    'bool' => [
                        'should' => [
                            [
                                'term' => [
                                    'type.keyword' => 'vote:down'
                                ]
                            ],
                            [
                                'term' => [
                                    'user_guid.keyword' => $this->accused->getUser()->guid
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $prepared = new Core\Data\ElasticSearch\Prepared\Search();
        $prepared->query($query);

        $result = $this->client->request($prepared);
        
        $posts= [];
        if ($result) {
            foreach ($result['hits']['hits'] as $row) {
                if (isset($row['_source']['entity_guid'])) {
                    $posts[] = Entities\Factory::build($row['_source']['entity_guid']);
                }
            }
        }

        return $posts;
    }

    private function getPosts()
    {
        $query = [
            'index' => $this->config->get('elasticsearch')['indexes']['search_prefix'] . '-activity',
            'size' => 1000,
            'body' => [
                'query' => [
                    'bool' => [
                        'should' => [
                            [
                                'term' => [
                                    'owner_guid.keyword' => $this->accused->getUser()->guid
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $prepared = new Core\Data\ElasticSearch\Prepared\Search();
        $prepared->query($query);

        $result = $this->client->request($prepared);

        $posts= [];
        if ($result) {
            foreach ($result['hits']['hits'] as $row) {
                if (isset($row['_source']['guid'])) {
                    $posts[] = Entities\Factory::build($row['_source']['guid']);
                }
            }
        }
        return $posts;
    }
}
