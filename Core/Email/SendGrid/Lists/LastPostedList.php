<?php
namespace Minds\Core\Email\SendGrid\Lists;

use Minds\Core\Di\Di;
use Minds\Core\Email\SendGrid\SendGridContact;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Data\ElasticSearch\Prepared\Search;
use Minds\Core\Log\Logger;
use Minds\Entities\User;

/**
 * Assembles timestamp of last post
 */
class LastPostedList implements SendGridListInterface
{
    // composite query after key (aggs bucket paging token).
    private $afterKey = null;

    private $maxTs;

    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Client $client = null,
        private ?Logger $logger = null
    ) {
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->client = $client ?: Di::_()->get('Database\ElasticSearch');
        $this->logger ??= Di::_()->get('Logger');
        $this->maxTs = time() * 1000;
    }

    /**
     * @return SendGridContact[]
     */
    public function getContacts(): iterable
    {
        $query = [
            'index' => 'minds-search-activity',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'range' => [
                                // last month
                                '@timestamp' => [
                                    'lt' => $this->maxTs,
                                    //'gt' => strtotime('24 hours ago', $this->maxTs / 1000) * 1000,
                                    'gt' => strtotime('90 days ago', $this->maxTs / 1000) * 1000,
                                    'format' => 'epoch_millis'
                                ]
                            ]
                        ]
                    ]
                ],
                // return no actual documents - we are querying for the buckets in the aggs.
                "size" => 0,
                "aggs" => [
                    "users" => [
                        // composite key allows us to paginate through buckets.
                        "composite" => [
                            "size" => 5000,
                            "sources" => [
                                [
                                    "user_guid" => [
                                        "terms" => [
                                            "field" => "owner_guid",
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        "aggregations" => [
                            "last_timestamp" => [
                                "max" => [
                                    "field" => "@timestamp"
                                ]
                            ]
                        ]
                    ]
                ],
                "_source" => false,
            ]
        ];

        // set after key (paging token).
        if ($this->getAfterKey()) {
            $query['body']['aggs']['users']['composite']['after'] = $this->getAfterKey();
        }

        $prepared = new Search();
        $prepared->query($query);

        $result = $this->client->request($prepared);

        foreach ($result["aggregations"]["users"]["buckets"] as $hit) {
            $owner = $this->entitiesBuilder->single($hit['key']['user_guid']);

            if (!$owner instanceof User) {
                continue;
            }

            $contact = new SendGridContact();
            $contact
                ->setUser($owner)
                ->setUserGuid($owner->getGuid())
                ->setUsername($owner->get('username'))
                ->setEmail($owner->getEmail())
                ->set('posts_count', $hit['doc_count'])
                ->set('posts_last_ts', date('c', $hit['last_timestamp']['value'] / 1000));

            if (!$contact->getEmail()) {
                continue;
            }

            yield $contact;
        }

        // if no we're not out of hits in the buckets.
        if (!empty($result["aggregations"]["users"]["buckets"]) && isset($result["aggregations"]["users"]["after_key"])) {
            // set after key again.
            $this->setAfterKey(
                $result["aggregations"]["users"]["after_key"] ?? null
            );

            // recursively yield.
            yield from $this->getContacts();
        }
    }

    /**
     * Gets after key - composite query after key (aggs bucket paging token).
     * @return ?array returns after key.
     */
    private function getAfterKey(): ?array
    {
        return $this->afterKey;
    }

    /**
     * Sets after key.
     * @param ?array composite query after key (aggs bucket paging token).
     */
    private function setAfterKey(?array $afterKey): void
    {
        $this->afterKey = $afterKey;
    }
}
