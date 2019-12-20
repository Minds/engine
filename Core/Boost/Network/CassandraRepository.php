<?php
/**
 * Cassandra Repository for Boost
 */
namespace Minds\Core\Boost\Network;

use Minds\Common\Urn;
use Minds\Common\Repository\Response;
use Minds\Core\Di\Di;
use Minds\Core\Data\Cassandra\Prepared;
use Cassandra;
use Minds\Helpers\Time;

class CassandraRepository
{
    /** @var Client $client */
    private $client;

    /** @var Urn $urn */
    private $urn;

    const SCHEMA_CURRENT = self::SCHEMA_V2;
    const SCHEMA_V1 = '04-2019';
    const SCHEMA_V2 = '12-2019';

    public function __construct($client = null, $urn = null)
    {
        $this->client = $client ?: Di::_()->get('Database\Cassandra\Cql');
        $this->urn = $urn ?: new Urn();
    }

    /**
     * Return a list of boosts
     * @param array $opts
     * @return Response
     */
    public function getList($opts = [])
    {
        $opts = array_merge([
            'limit' => 12,
            'token' => null
        ], $opts);

        $template = "SELECT * FROM boosts WHERE type = ?";
        $values = [ (string) $opts['type'] ];

        if ($opts['guids']) {
            $collection = Cassandra\Type::collection(Cassandra\Type::varint())->create(...array_values(array_map(function ($guid) {
                return new Cassandra\Varint($guid);
            }, $opts['guids'])));

            $template .= " AND guid IN ?";
            $values[] = $collection;
        }

        $query = new Prepared\Custom();
        $query->query($template, $values);
        
        $query->setOpts([
            'page_size' => (int) $opts['limit'],
            'paging_state_token' => base64_decode($opts['token'], true)
        ]);

        $response = new Response();

        try {
            $result = $this->client->request($query);

            foreach ($result as $row) {
                $data = json_decode($row['data'], true);
                $data = $this->updateTimestampsToMsValues($data);
                $data = $this->updateOldSchema($data);

                if (isset($data['boost_type'])) {
                    $boostType = $data['boost_type'];
                    if ($boostType === Boost::BOOST_TYPE_CAMPAIGN) {
                        $boost = new Campaign();
                        $boost->setName($data['name']);
                        $boost->setStart($data['start']);
                        $boost->setEnd($data['end']);
                        $boost->setBudget($data['budget']);
                        $boost->setPaused($data['paused']);
                    } else {
                        $boost = new Boost();
                    }
                    $boost->setBoostType($boostType);
                } else {
                    $boost = new Boost();
                }

                $boost->setGuid((string) $row['guid'])
                    ->setEntityGuid($data['entity_guid'])
                    ->setOwnerGuid($data['owner_guid'])
                    ->setType($row['type'])
                    ->setCreatedTimestamp($data['created'])
                    ->setReviewedTimestamp($data['reviewed'])
                    ->setRevokedTimestamp($data['revoked'])
                    ->setRejectedTimestamp($data['rejected'])
                    ->setCompletedTimestamp($data['completed'])
                    ->setBid($data['bid'])
                    ->setBidType($data['bidType'])
                    ->setImpressions($data['impressions'])
                    ->setTransactionId($data['transactionId'])
                    ->setPriority($data['priority'])
                    ->setRating($data['rating'])
                    ->setTags($data['tags'])
                    ->setNsfw($data['nsfw'])
                    ->setRejectedReason($data['rejection_reason'])
                    ->setChecksum($data['checksum']);
                
                $response[] = $boost;
            }

            $response->setPagingToken(base64_encode($result->pagingStateToken()));
        } catch (\Exception $e) {
            // TODO: Log or warning
        }

        return $response;
    }

    /**
     * Update any s timestamps to ms values
     * @param array $data
     * @return array
     */
    protected function updateTimestampsToMsValues(array $data): array
    {
        $this->convertTimestampToMsInDataArray('last_updated', $data);
        $this->convertTimestampToMsInDataArray('time_created', $data);
        $this->convertTimestampToMsInDataArray('@created', $data);

        return $data;
    }

    protected function convertTimestampToMsInDataArray(string $key, array &$array)
    {
        if (!empty($array[$key])) {
            $timestampInt = intval($array[$key]);
            if ($timestampInt < Time::HISTORIC_MS_VALUE) {
                $array[$key] = Time::sToMs($timestampInt);
            }
        }
    }

    /**
     * Update the old schema
     * @param array $data
     * @return array
     */
    protected function updateOldSchema(array $data): array
    {
        if (!isset($data['schema']) && $data['schema'] != self::SCHEMA_CURRENT) {
            if (!isset($data['schema']) && $data['schema'] != self::SCHEMA_V1) {
                $data['entity_guid'] = $data['entity']['guid'];
                $data['owner_guid'] = $data['owner']['guid'];
                $data['@created'] = $data['time_created'];
                $data['@reviewed'] = $data['state'] === Boost::STATE_APPROVED ? $data['last_updated'] : null;
                $data['@revoked'] = $data['state'] === Boost::STATE_REVOKED ? $data['last_updated'] : null;
                $data['@rejected'] = $data['state'] === Boost::STATE_REJECTED ? $data['last_updated'] : null;
                $data['@completed'] = $data['state'] === Boost::STATE_COMPLETED ? $data['last_updated'] : null;
                unset($data['time_created']);
                unset($data['last_updated']);
                $data['schema'] = self::SCHEMA_V1;
            }

            if (!isset($data['schema']) && $data['schema'] != self::SCHEMA_V2) {
                $data['created'] = $data['@created'];
                $data['reviewed'] = $data['@reviewed'];
                $data['revoked'] = $data['@revoked'];
                $data['rejected'] = $data['@rejected'];
                $data['completed'] = $data['@completed'];
                unset($data['@created']);
                unset($data['@reviewed']);
                unset($data['@revoked']);
                unset($data['@rejected']);
                unset($data['@completed']);
                $data['schema'] = self::SCHEMA_V2;
            }
        }

        return $data;
    }

    /**
     * Return a single boost via urn
     * @param string $urn
     * @return Boost
     */
    public function get($urn)
    {
        list($type, $guid) = explode(':', $this->urn->setUrn($urn)->getNss(), 2);
        return $this->getList([
            'type' => $type,
            'guids' => [ $guid ],
        ])[0];
    }

    /**
     * Add a boost
     * @param Boost $boost
     * @return bool
     */
    public function add($boost)
    {
        if (!$boost->getType()) {
            throw new \Exception('Type is required');
        }

        if (!$boost->getGuid()) {
            throw new \Exception('GUID is required');
        }

        if (!$boost->getOwnerGuid()) {
            throw new \Exception('Owner is required');
        }

        $template = "INSERT INTO boosts
            (type, guid, owner_guid, state, data)
            VALUES
            (?, ?, ?, ?, ?)
        ";

        $data = $boost->export();

        /* Additional parameters that differ from boost export */
        $data['schema'] = self::SCHEMA_CURRENT;
        $data['bidType'] = in_array($boost->getBidType(), ['onchain', 'offchain'], true) ? 'tokens' : $boost->getBidType();
        $data['handler'] = $boost->getType();

        $values = [
            (string) $boost->getType(),
            new Cassandra\Varint($boost->getGuid()),
            new Cassandra\Varint($boost->getOwnerGuid()),
            (string) $boost->getState(),
            json_encode($data)
        ];

        $query = new Prepared\Custom();
        $query->query($template, $values);

        try {
            $success = $this->client->request($query);
        } catch (\Exception $e) {
            return false;
        }

        return $success;
    }

    /**
     * Update a boost
     * @param Boost $boost
     * @return bool
     */
    public function update($boost, $fields = [])
    {
        return $this->add($boost);
    }

    /**
     * void
     */
    public function delete($boost)
    {
    }
}
