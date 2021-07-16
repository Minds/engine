<?php
namespace Minds\Core\Analytics\EntityCentric;

use Minds\Core\Data\ElasticSearch;
use Minds\Core\Di\Di;
use Minds\Core\Analytics\UserStates\ActiveUsersIterator;
use Minds\Core\EntitiesBuilder;

class ReferralsSynchroniser
{
    /** @var array */
    private $records = [];

    /** @var ElasticSearch\Client */
    private $es;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var int */
    protected $from;

    public function __construct($es = null, $entitiesBuilder = null)
    {
        $this->es = $es ?? Di::_()->get('Database\ElasticSearch');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * @param int $from
     * @return self
     */
    public function setFrom($from): self
    {
        $this->from = $from;
        return $this;
    }

    /**
     * Convert to records
     * @return iterable
     */
    public function toRecords(): iterable
    {
        $i = 0;
        foreach ($this->getReferralCounts([ 'referrers' => true ]) as $bucket) {
            $urn = "urn:user:{$bucket['key']}";
            $count = $bucket['doc_count'];

            $record = new EntityCentricRecord();
            $record->setEntityUrn($urn)
                ->setOwnerGuid($bucket['key'])
                ->setTimestamp($this->from)
                ->setResolution('day');

            $record->incrementSum('referral::total', (int) $count);
            $this->records[] = $record;
            ++$i;
            error_log("Referrals (total): $i");
        }

        // TODO: now get the users who are active
        $i = 0;
        foreach ($this->getActiveCounts() as $bucket) {
            $urn = "urn:user:{$bucket['referrerGuid']}";
            $count = $bucket['count'];

            $record = new EntityCentricRecord();
            $record->setEntityUrn($urn)
                ->setOwnerGuid($bucket['referrerGuid'])
                ->setTimestamp($bucket['timestamp'])
                ->setResolution('day');

            $record->incrementSum('referral::active', $count);
            $this->records[] = $record;
            ++$i;
            error_log("Referrals (active): $i [$count]");
        }

        foreach ($this->records as $record) {
            yield $record;
        }
    }

    /**
     * Return the counts of referrals
     * @param array $opts
     * @return iterable
     */
    private function getReferralCounts(array $opts): iterable
    {
        $opts = array_merge([
            'from' => $this->from,
            'referrers' => true,
        ], $opts);

        $must = [];

        $must[] = [
            'term' => [
                'action.keyword' => 'signup',
            ],
        ];

        $must[] = [
            'exists' => [
                'field' => 'referrer_guid',
            ],
        ];

        $must[] = [
            'range' => [
                '@timestamp' => [
                    'gte' => $opts['from'] * 1000,
                    'lt' => strtotime('+1 day', $opts['from']) * 1000,
                ],
            ],
        ];

        $partition = 0;
        $partitions = 50;
        $partitionSize = 5000; // Allows for 250,000 entities
        $index = 'minds-metrics-*';

        while (++$partition < $partitions) {
            // Do the query
            $query = [
                'index' => $index,
                'size' => 0,
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => $must,
                        ],
                    ],
                    'aggs' => [
                        '1' => [
                            'terms' => [
                                'field' => $opts['referrers'] ? 'referrer_guid' : 'user_guid.keyword',
                                'min_doc_count' =>  1,
                                'size' => $partitionSize,
                                'include' => [
                                    'partition' => $partition,
                                    'num_partitions' => $partitions,
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            // Query elasticsearch
            $prepared = new ElasticSearch\Prepared\Search();
            $prepared->query($query);
            $response = $this->es->request($prepared);


            foreach ($response['aggregations']['1']['buckets'] as $bucket) {
                yield $bucket;
            }
        }
    }

    /**
     * To get active users we go back 7 days and collect their daily activity buckets
     * If they are active for 4/7 days then they are active
     * @return iterable
     */
    private function getActiveCounts(): iterable
    {
        $referrers = [];
        $activeUsersIterator = new ActiveUsersIterator();

        $signupDate = strtotime('-7 days', $this->from);

        $userGuids = array_map(function ($bucket) {
            return $bucket['key'];
        }, iterator_to_array($this->getReferralCounts([
            'referrers' => false,
            'from' => $signupDate,
        ]))); // false returns referrees instead of referrers

        $activeUsersIterator
            ->setReferenceDate(strtotime('+1 day', $this->from))
            ->setRangeOffset(7) // Go back 7 days
            ->setFilterUserGuids($userGuids);
        foreach ($activeUsersIterator as $bucket) {
            $pct = $bucket->getActivityPercentage();
            if ($pct < 0.5) {
                continue;
            }
            $user = $this->entitiesBuilder->single($bucket->getUserGuid());
            if ($user->referrer) {
                $referrers[$user->referrer] = ($referrers[$user->referrer] ?? 0) + 1;
                error_log("Referral active: {$bucket->getUserGuid()} referrer: $user->referrer total: {$referrers[$user->referrer]} joined: " . date('c', $user->time_created));
            }
        }
        foreach ($referrers as $referrer => $count) {
            yield [
                'referrerGuid' => $referrer,
                'timestamp' => $signupDate,
                'count' => $count,
            ];
        }
    }
}
