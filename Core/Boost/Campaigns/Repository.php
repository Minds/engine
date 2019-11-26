<?php

namespace Minds\Core\Boost\Campaigns;

use Cassandra\Bigint;
use Cassandra\Rows;
use Exception;
use Minds\Common\Repository\Response;
use Minds\Core\Data\Cassandra\Client as CassandraClient;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Di\Di;
use Minds\Helpers\Number;
use Minds\Helpers\Text;
use Minds\Traits\DiAlias;

class Repository
{
    use DiAlias;

    /** @var CassandraClient */
    protected $db;

    /**
     * Repository constructor.
     * @param CassandraClient $db
     */
    public function __construct($db = null)
    {
        $this->db = $db ?: Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * @param array $opts
     * @return Response
     * @throws Exception
     */
    public function getCampaignByGuid(array $opts = [])
    {
        $opts = array_merge([
            'limit' => 12,
            'offset' => null,
            'guid' => null,
        ], $opts);

        if (!$opts['guid']) {
            throw new \Exception('Cassandra getList only supports GUID constraint');
        }

        $cql = "SELECT * FROM boost_campaigns WHERE guid = ?";
        $values = [
            new Bigint($opts['guid'])
        ];
        $cqlOpts = [];


        if ($opts['limit']) {
            $cqlOpts['page_size'] = (int) $opts['limit'];
        }

        if ($opts['offset']) {
            $cqlOpts['paging_state_token'] = base64_decode($opts['offset'], true);
        }

        $prepared = new Custom();
        $prepared->query($cql, $values);
        $prepared->setOpts($cqlOpts);

        $response = new Response();

        try {
            /** @var Rows $rows */
            $rows = $this->db->request($prepared);

            if ($rows) {
                foreach ($rows as $row) {
                    $campaign = new Campaign();
                    $campaign
                        ->setUrn("urn:campaign:{$row['guid']}")
                        ->setOwnerGuid($row['owner_guid'])
                        ->setType($row['type']);

                    $json_data = json_decode($row['json_data'] ?: '{}', true);

                    $campaign
                        ->setName($json_data['name'])
                        ->setEntityUrns(Text::buildArray($json_data['entity_urns']))
                        ->setHashtags(Text::buildArray($json_data['hashtags']))
                        ->setNsfw(Number::buildIntArray($json_data['nsfw']))
                        ->setStart((int) $json_data['start'])
                        ->setEnd((int) $json_data['end'])
                        ->setBudget((string) $json_data['budget'])
                        ->setBudgetType($json_data['budget_type'])
                        ->setChecksum($json_data['checksum'])
                        ->setImpressions((int) $json_data['impressions'])
                        ->setImpressionsMet($json_data['impressions_met'])
                        ->setRating($json_data['rating'])
                        ->setQuality($json_data['quality'])
                        ->setCreatedTimestamp(((int) $json_data['created_timestamp']) ?: null)
                        ->setReviewedTimestamp(((int) $json_data['reviewed_timestamp']) ?: null)
                        ->setRejectedTimestamp(((int) $json_data['rejected_timestamp']) ?: null)
                        ->setRevokedTimestamp(((int) $json_data['revoked_timestamp']) ?: null)
                        ->setCompletedTimestamp(((int) $json_data['completed_timestamp']) ?: null);

                    $response[] = $campaign;
                }

                $response->setPagingToken(base64_encode($rows->pagingStateToken()));
                $response->setLastPage($rows->isLastPage());
            }
        } catch (Exception $e) {
            $response->setException($e);
        }

        return $response;
    }

    /**
     * @param Campaign $campaign
     * @param bool $async
     * @return bool
     */
    public function putCampaign(Campaign $campaign, $async = true)
    {
        $cql = "INSERT INTO boost_campaigns (type, guid, owner_guid, json_data, delivery_status) VALUES (?, ?, ?, ?, ?)";

        $values = [
            $campaign->getType(),
            new Bigint($campaign->getGuid()),
            new Bigint($campaign->getOwnerGuid()),
            json_encode($campaign->getData()),
            $campaign->getDeliveryStatus(),
        ];

        $prepared = new Custom();
        $prepared->query($cql, $values);

        return (bool) $this->db->request($prepared, $async);
    }
}
