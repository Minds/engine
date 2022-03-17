<?php
namespace Minds\Core\Email\SendGrid\Lists;

use Minds\Core\Analytics\Metrics\Active;
use Minds\Core\Di\Di;
use Minds\Core\Email\SendGrid\SendGridContact;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Data\ElasticSearch\Client;
use \Minds\Core\Data\ElasticSearch\Prepared\Search;

/**
 * Assembles list of users who have been active in last 30 days.
 */
class Active30DayList implements SendGridListInterface
{
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
                                '@timestamp' => [
                                    'gte' => strtotime('midnight -30 day') * 1000,
                                    'lt' => strtotime('midnight') * 1000,
                                    'format' => 'epoch_millis'
                                ]
                            ]
                        ]
                    ]
                ],
                "size" => 0,
                "aggs" => [
                    "unique_users" => [
                      "terms" => [
                        "field" => "user_guid.keyword"
                      ]
                    ]
                ],
                "_source" => false,
            ]
        ];

        $prepared = new Search();
        $prepared->query($query);

        $result = $this->client->request($prepared);

        foreach ($result["aggregations"]["unique_users"]["buckets"] as $hit) {
            $owner = $this->entitiesBuilder->single($hit['key']);

            if (!$owner) {
                continue;
            }

            $contact = new SendGridContact();
            $contact
                ->setUserGuid($owner->getGuid())
                ->setUsername($owner->get('username'))
                ->setEmail($owner->getEmail());

            if (!$contact->getEmail()) {
                continue;
            }

            yield $contact;
        }
    }
}
