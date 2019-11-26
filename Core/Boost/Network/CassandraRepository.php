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
                $boost = new Boost();
                $data = json_decode($row['data'], true);

                $data = $this->updateTimestampsToMsValues($data);

                if (!isset($data['schema']) && $data['schema'] != '04-2019') {
                    $data = $this->updateOldSchema($data);
                }

                $boost->setGuid((string) $row['guid'])
                    ->setEntityGuid($data['entity_guid'])
                    ->setOwnerGuid($data['owner_guid'])
                    ->setType($row['type'])
                    ->setCreatedTimestamp($data['@created'])
                    ->setReviewedTimestamp($data['@reviewed'])
                    ->setRevokedTimestamp($data['@revoked'])
                    ->setRejectedTimestamp($data['@rejected'])
                    ->setCompletedTimestamp($data['@completed'])
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
        $data['entity_guid'] = $data['entity']['guid'];
        $data['owner_guid'] = $data['owner']['guid'];
        $data['@created'] = $data['time_created'];
        $data['@reviewed'] = $data['state'] === Boost::STATE_APPROVED ? $data['last_updated'] : null;
        $data['@revoked'] = $data['state'] === Boost::STATE_REVOKED ? $data['last_updated'] : null;
        $data['@rejected'] = $data['state'] === Boost::STATE_REJECTED ? $data['last_updated'] : null;
        $data['@completed'] = $data['state'] === Boost::STATE_COMPLETED ? $data['last_updated'] : null;
        $data['schema'] = '04-2019';

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

        $data = [
            'guid' => $boost->getGuid(),
            'schema' => '04-2019',
            'entity_guid' => $boost->getEntityGuid(),
            'entity' => $boost->getEntity() ? $boost->getEntity()->export() : null, //TODO: remove once on production
            'bid' => $boost->getBid(),
            'impressions' => $boost->getImpressions(),
            'bidType' => in_array($boost->getBidType(), [ 'onchain', 'offchain' ], true) ? 'tokens' : $boost->getBidType(), //TODO: remove once on production
            'owner_guid' => $boost->getOwnerGuid(),
            'owner' => $boost->getOwner() ? $boost->getOwner()->export() : null, //TODO: remove once on production
            '@created' => $boost->getCreatedTimestamp(),
            '@reviewed' => $boost->getReviewedTimestamp(),
            '@rejected' => $boost->getRejectedTimestamp(),
            '@revoked' => $boost->getRevokedTimestamp(),
            '@completed' => $boost->getCompletedTimestamp(),
            'transactionId' => $boost->getTransactionId(),
            'type' => $boost->getType(),
            'handler' => $boost->getType(), //TODO: remove once on production
            'state' => $boost->getState(), //TODO: remove once on production
            'priority' => $boost->getPriority(),
            'rating' => $boost->getRating(),
            'tags' => $boost->getTags(),
            'nsfw' => $boost->getNsfw(),
            'rejection_reason'=> $boost->getRejectedReason(),
            'checksum' => $boost->getChecksum(),
        ];

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
