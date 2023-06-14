<?php
namespace Minds\Core\Email\SendGrid\Lists;

use Minds\Core\Di\Di;
use Minds\Core\Email\SendGrid\SendGridContact;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Data\ElasticSearch\Client;
use Minds\Core\Data\ElasticSearch\Prepared\Search;
use Minds\Core\Log\Logger;
use Minds\Entities\User;
use Minds\Helpers\Flags;

/**
 * Assembles previous activity levels
 */
class KiteStatesList implements SendGridListInterface
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
        $this->afterKey = [
            "user_guid" => "971441316581875716"
        ];
    }

    /**
     * @return SendGridContact[]
     */
    public function getContacts(): iterable
    {
        $query = [
            'index' => 'minds-kite',
            'body' => [
                'query' => [
                    'bool' => [
                        'must_not' => [
                            [
                                'terms' => [
                                    'state' => [
                                        'cold',
                                        'new',
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                // return no actual documents - we are querying for the buckets in the aggs.
                "size" => 0,
                "aggs" => [
                    "user_states" => [
                        // composite key allows us to paginate through buckets.
                        "composite" => [
                            "size" => 5000,
                            "sources" => [
                                [
                                    "user_guid" => [
                                        "terms" => [
                                            "field" => "user_guid",
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        "aggregations" => [
                            "state" => [
                                "terms" => [
                                    "field" => "state"
                                ]
                            ],
                            "last_state" => [
                                "max" => [
                                    "field" => "reference_date"
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
            $query['body']['aggs']['user_states']['composite']['after'] = $this->getAfterKey();
        }

        $prepared = new Search();
        $prepared->query($query);

        $result = $this->client->request($prepared);

        foreach ($result["aggregations"]["user_states"]["buckets"] as $hit) {
            // if ($hit['last_state']['value'] < strtotime('midnight') * 1000) {
            //     continue;
            // }

            $owner = $this->entitiesBuilder->single($hit['key']['user_guid']);

            if (!$owner instanceof User || Flags::shouldFail($owner) || !$owner->getEmail() || !$owner->isTrusted()) {
                continue;
            }

            $contact = new SendGridContact();
            $contact
                ->setUser($owner)
                ->setUserGuid($owner->getGuid())
                ->setUsername($owner->get('username'))
                ->setEmail($owner->getEmail())
                ->set('kite_last_ts', date('c', $hit['last_state']['value'] / 1000));

            foreach ($hit['state']['buckets'] as $states) {
                $contact->set('kite_' . $states['key'] . '_count', $states['doc_count']);
                $friendlySignupDate = date('c', $owner->getTimeCreated());
                $this->logger->info("[KiteStatesList] {$owner->getGuid()} has been {$states['key']} {$states['doc_count']} times - $friendlySignupDate");
            }

            yield $contact;
        }

        // if no we're not out of hits in the buckets.
        if (!empty($result["aggregations"]["user_states"]["buckets"]) && isset($result["aggregations"]["user_states"]["after_key"])) {
            // set after key again.
            $this->setAfterKey(
                $result["aggregations"]["user_states"]["after_key"] ?? null
            );

            unset($result); // Fixes memory leak

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
