<?php
namespace Minds\Core\Email\SendGrid\Lists;

use Minds\Core\Di\Di;
use Minds\Core\Email\SendGrid\SendGridContact;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Data\ElasticSearch\Prepared\Search;
use Minds\Entities\User;

/**
 * Assembles list of users who have been active in last 30 days.
 */
class Active30DayList implements SendGridListInterface
{
    // composite query after key (aggs bucket paging token).
    private $afterKey = null;

    public function __construct(
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?Client $client = null,
    ) {
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->client = $client ?: Di::_()->get('Database\ElasticSearch');
    }

    /**
     * Gets users who have been active in last 30 days.
     * @return SendGridContact[] array of contacts who have been active in last 30 days.
     */
    public function getContacts(): iterable
    {
        $query = [
            'index' => 'minds-metrics-*',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'range' => [
                                // last month
                                '@timestamp' => [
                                    'gte' => strtotime('midnight -30 day') * 1000,
                                    'lt' => strtotime('midnight') * 1000,
                                    'format' => 'epoch_millis'
                                ]
                            ]
                        ]
                    ]
                ],
                // return no actual documents - we are querying for the buckets in the aggs.
                "size" => 0,
                "aggs" => [
                    "unique_users" => [
                        // composite key allows us to paginate through buckets.
                        "composite" => [
                            // page size.
                            "size" => 5000,
                            "sources" => [
                                [
                                    "user_guid" => [
                                        // distinct user_guids
                                        "terms" => [
                                            "field" => "user_guid.keyword",
                                        ]
                                    ]
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
            $query['body']['aggs']['unique_users']['composite']['after'] = $this->getAfterKey();
        }

        $prepared = new Search();
        $prepared->query($query);

        $result = $this->client->request($prepared);

        foreach ($result["aggregations"]["unique_users"]["buckets"] as $hit) {
            $owner = $this->entitiesBuilder->single($hit['key']['user_guid']);

            if (!$owner instanceof User) {
                continue;
            }

            $contact = new SendGridContact();
            $contact
                ->setUserGuid($owner->getGuid())
                ->setUsername($owner->get('username'))
                ->setEmail($owner->getEmail())
                ->setLastActive30DayTs(time());

            if (!$contact->getEmail()) {
                continue;
            }

            yield $contact;
        }

        // if no we're not out of hits in the buckets.
        if (!empty($result["aggregations"]["unique_users"]["buckets"])) {
            // set after key again.
            $this->setAfterKey(
                $result["aggregations"]["unique_users"]["after_key"] ?? null
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
