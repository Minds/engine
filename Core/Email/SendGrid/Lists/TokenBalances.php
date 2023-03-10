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
 * Assembles token balances of all users.
 */
class TokenBalances implements SendGridListInterface
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
            'index' => 'minds-offchain*',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'range' => [
                                // last month
                                '@timestamp' => [
                                    'lt' => $this->maxTs,
                                    'format' => 'epoch_millis'
                                ]
                            ]
                        ]
                    ]
                ],
                // return no actual documents - we are querying for the buckets in the aggs.
                "size" => 0,
                "aggs" => [
                    "user_balances" => [
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
                            "balance" => [
                                "sum" => [
                                    "field" => "amount"
                                ]
                            ],
                            "last_transaction" => [
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
            $query['body']['aggs']['user_balances']['composite']['after'] = $this->getAfterKey();
        }

        $prepared = new Search();
        $prepared->query($query);

        $result = $this->client->request($prepared);

        foreach ($result["aggregations"]["user_balances"]["buckets"] as $hit) {
            if ($hit['last_transaction']['value'] < strtotime('midnight') * 1000) {
                continue;
            }

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
                ->set('token_balance', $hit['balance']['value'])
                ->set('token_last_ts', date('c', $hit['last_transaction']['value'] / 1000));

            if (!$contact->getEmail()) {
                continue;
            }

            $this->logger->info("[TokenBalance] {$owner->getGuid()} {$hit['balance']['value']}");

            yield $contact;
        }

        // if no we're not out of hits in the buckets.
        if (!empty($result["aggregations"]["user_balances"]["buckets"]) && isset($result["aggregations"]["user_balances"]["after_key"])) {
            // set after key again.
            $this->setAfterKey(
                $result["aggregations"]["user_balances"]["after_key"] ?? null
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
