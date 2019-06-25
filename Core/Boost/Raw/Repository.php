<?php
/**
 * Repository
 * @author edgebal
 */

namespace Minds\Core\Boost\Raw;

use Cassandra;
use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Data\Cassandra\Client as CassandraClient;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Di\Di;
use NotImplementedException;

class Repository
{
    /** @var CassandraClient */
    protected $db;

    /**
     * Repository constructor.
     * @param CassandraClient $db
     */
    public function __construct(
        $db = null
    )
    {
        $this->db = $db ?: Di::_()->get('Database\Cassandra\Cql');
    }

    public function getList(array $opts = [])
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

        $query = new Custom();
        $query->query($template, $values);

        $query->setOpts([
            'page_size' => (int) $opts['limit'],
            'paging_state_token' => base64_decode($opts['offset'])
        ]);

        $response = new Response();

        try {
            $result = $this->db->request($query);

            foreach ($result as $row) {
                $rawBoost = new RawBoost();
                $data = json_decode($row['data'], true);

                if (!isset($data['schema']) && $data['schema'] != '04-2019') {
                    $data['entity_guid'] = $data['entity']['guid'];
                    $data['owner_guid'] = $data['owner']['guid'];
                    $data['@created'] = $data['time_created'] * 1000;
                    $data['@reviewed'] = $data['state'] === 'accepted' ? ($data['last_updated'] * 1000) : null;
                    $data['@revoked'] = $data['state'] === 'revoked' ? ($data['last_updated'] * 1000) : null;
                    $data['@rejected'] = $data['state'] === 'rejected' ? ($data['last_updated'] * 1000) : null;
                    $data['@completed'] = $data['state'] === 'completed' ? ($data['last_updated'] * 1000) : null;
                }

                if ($data['@created'] < 1055503139000) {
                    $data['@created'] = $data['@created'] * 1000;
                }

                if ($data['is_campaign'] ?? false) {
                    // Skip campaigns
                    continue;
                }

                $rawBoost
                    ->setGuid((string) $row['guid'])
                    ->setOwnerGuid((string) $data['owner_guid'])
                    ->setType($row['type'])
                    ->setEntityGuid((string) $data['entity_guid'])
                    ->setEntityUrns($data['entity_urns'] ?? [])
                    ->setBid($data['bid'])
                    ->setBidType($data['bidType'])
                    ->setTokenMethod($data['token_method'] ?? null)
                    ->setPriority($data['priority'])
                    ->setRating($data['rating'])
                    ->setImpressions($data['impressions'])
                    ->setTags($data['tags'])
                    ->setNsfw($data['nsfw'])
                    ->setTransactionId($data['transactionId'])
                    ->setRejectionReason($data['rejection_reason'])
                    ->setChecksum($data['checksum'])
                    ->setCampaign((bool) $data['is_campaign'] ?? false)
                    ->setCampaignName($data['campaign_name'] ?? null)
                    ->setCampaignStart($data['campaign_start'] ?? null)
                    ->setCampaignEnd($data['campaign_end'] ?? null)
                    ->setCreatedTimestamp($data['@created'])
                    ->setReviewedTimestamp($data['@reviewed'])
                    ->setRevokedTimestamp($data['@revoked'])
                    ->setRejectedTimestamp($data['@rejected'])
                    ->setCompletedTimestamp($data['@completed'])
                    ->setMongoId($data['_id'])
                ;

                $response[] = $rawBoost;
            }

            $response->setPagingToken(base64_encode($result->pagingStateToken()));
        } catch (Exception $e) {
            $response->setException($e);
        }

        return $response;
    }

    /**
     * @param RawBoost $rawBoost
     * @return bool
     * @throws Exception
     */
    public function add(RawBoost $rawBoost)
    {
        if (!$rawBoost->getType()) {
            throw new Exception('Missing type');
        }

        if (!$rawBoost->getGuid()) {
            throw new Exception('Missing GUID');
        }

        if (!$rawBoost->getOwnerGuid()) {
            throw new Exception('Missing owner GUID');
        }

        $template = "INSERT INTO boosts
            (type, guid, owner_guid, destination_guid, mongo_id, state, data)
            VALUES
            (?, ?, ?, ?, ?, ?, ?)
        ";

        $data = [
            'schema' => '04-2019',
            'guid' => $rawBoost->getGuid(),
            'entity_guid' => $rawBoost->getEntityGuid(),
            'entity_urns' => $rawBoost->getEntityUrns(),
            'bid' => $rawBoost->getBid(),
            'impressions' => $rawBoost->getImpressions(),
            'bidType' => $rawBoost->getBidType(),
            'owner_guid' => $rawBoost->getOwnerGuid(),
            'transactionId' => $rawBoost->getTransactionId(),
            'type' => $rawBoost->getType(),
            'priority' => $rawBoost->isPriority(),
            'rating' => $rawBoost->getRating(),
            'tags' => $rawBoost->getTags(),
            'nsfw' => $rawBoost->getNsfw(),
            'rejection_reason'=> $rawBoost->getRejectionReason(),
            'checksum' => $rawBoost->getChecksum(),
            '@created' => $rawBoost->getCreatedTimestamp(),
            '@reviewed' => $rawBoost->getReviewedTimestamp(),
            '@rejected' => $rawBoost->getRejectedTimestamp(),
            '@revoked' => $rawBoost->getRevokedTimestamp(),
            '@completed' => $rawBoost->getCompletedTimestamp(),
            'token_method' => $rawBoost->getTokenMethod(),
            'is_campaign' => $rawBoost->isCampaign(),
            'campaign_name' => $rawBoost->getCampaignName(),
            'campaign_start' => $rawBoost->getCampaignStart(),
            'campaign_end' => $rawBoost->getCampaignEnd(),

            // Legacy.

            '_id' => $rawBoost->getMongoId(),
            'entity' => $rawBoost->getEntity() ? $rawBoost->getEntity()->export() : null,
            'owner' => $rawBoost->getOwner() ? $rawBoost->getOwner()->export() : null,
            'time_created' => $rawBoost->getCreatedTimestamp(),
            'last_updated' => time(),
            'handler' => $rawBoost->getType(),
            'state' => $rawBoost->getState(),
        ];

        $values = [
            (string) $rawBoost->getType(),
            new Cassandra\Varint($rawBoost->getGuid()),
            new Cassandra\Varint($rawBoost->getOwnerGuid()),
            null,
            (string) $rawBoost->getMongoId(),
            (string) $rawBoost->getState(),
            json_encode($data)
        ];

        $query = new Custom();
        $query->query($template, $values);

        try {
            $success = (bool) $this->db->request($query);
        } catch (Exception $e) {
            return false;
        }

        return $success;
    }

    /**
     * @param RawBoost $rawBoost
     * @return bool
     * @throws Exception
     */
    public function update(RawBoost $rawBoost)
    {
        return $this->add($rawBoost);
    }

    /**
     * @param RawBoost $rawBoost
     * @throws NotImplementedException
     */
    public function delete(RawBoost $rawBoost)
    {
        throw new NotImplementedException();
    }
}
