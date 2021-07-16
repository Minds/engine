<?php
/**
 * Cassandra Repository for Referrals
 */
namespace Minds\Core\Referrals;

use Minds\Common\Repository\Response;
use Minds\Core\Di\Di;
use Minds\Core\Data\Cassandra\Prepared;
use Cassandra;
use Cassandra\Bigint;
use Minds\Common\Urn;

class Repository
{
    /** @var Client $client */
    private $client;

    /** @var Urn $urn */
    protected $urn;

    public function __construct($client = null, $urn = null)
    {
        $this->client = $client ?: Di::_()->get('Database\Cassandra\Cql');
        $this->urn = $urn ?: new Urn;
    }

    /**
     * Return a single referral
     * @param string $urn
     * @return Referral
     */
    public function get($urn)
    {
        $parts = explode('-', $this->urn->setUrn($urn)->getNss());

        if (!$parts[0] && !$parts[1]) {
            return null;
        }

        $referrerGuid = $parts[0];
        $prospectGuid = $parts[1];

        $response = $this->getList([
            'referrer_guid' => $referrerGuid,
            'prospect_guid' => $prospectGuid,
        ]);

        if (!$response[0]) {
            return null;
        }

        return $response[0];
    }

    /**
     * Return a list of referrals
     * @param array $opts 'limit', 'offset', 'referrer_guid', 'prospect_guid'
     * @return Response
     * @throws \Exception
     */
    public function getList($opts = [])
    {
        $opts = array_merge([
            'limit' => 12,
            'offset' => '',
            'referrer_guid' => null,
            'prospect_guid' => null,
        ], $opts);

        if (!$opts['referrer_guid']) {
            throw new \Exception('Referrer GUID is required');
        }

        $response = new Response;

        $statement = "SELECT * FROM referrals";

        $where = ["referrer_guid = ?"];
        $values = [new Bigint($opts['referrer_guid'])];

        if ($opts['prospect_guid']) {
            $where[] = "prospect_guid = ?";
            $values[] = new Bigint($opts['prospect_guid']);
        }

        $statement .= " WHERE " . implode(' AND ', $where);


        $cqlOpts = [];
        if ($opts['limit']) {
            $cqlOpts['page_size'] = (int) $opts['limit'];
        }

        if ($opts['offset']) {
            $cqlOpts['paging_state_token'] = base64_decode($opts['offset'], true);
        }

        $query = new Prepared\Custom();
        $query->query($statement, $values);
        $query->setOpts($cqlOpts);

        try {
            $rows = $this->client->request($query);

            foreach ($rows as $row) {
                $referral = new Referral();

                $referral->setProspectGuid((string) $row['prospect_guid'])
                    ->setReferrerGuid((string) $row['referrer_guid'])
                    ->setRegisterTimestamp(isset($row['register_timestamp']) ? (int) $row['register_timestamp']->time() : null)
                    ->setJoinTimestamp(isset($row['join_timestamp']) ? (int) $row['join_timestamp']->time() : null)
                    ->setPingTimestamp(isset($row['ping_timestamp']) ? (int) $row['ping_timestamp']->time() : null);

                $response[] = $referral;
            }

            $response->setPagingToken(base64_encode($rows->pagingStateToken()));
            $response->setLastPage($rows->isLastPage());
        } catch (\Exception $e) {
            // $response = $e;
            return $response;
        }

        return $response;
    }


    /**
     * Add a referral
     * @param Referral $referral
     * @return bool
     * @throws \Exception
     */
    public function add(Referral $referral)
    {
        if (!$referral->getReferrerGuid()) {
            throw new \Exception('Referrer GUID is required', 404);
        }

        if (!$referral->getProspectGuid()) {
            throw new \Exception('Prospect GUID is required', 404);
        }

        if (!$referral->getRegisterTimestamp()) {
            throw new \Exception('Register timestamp is required');
        }

        $template = "INSERT INTO referrals
            (referrer_guid, prospect_guid, register_timestamp)
            VALUES
            (?, ?, ?)
        ";

        $values = [
            new Cassandra\Bigint($referral->getReferrerGuid()),
            new Cassandra\Bigint($referral->getProspectGuid()),
            new Cassandra\Timestamp($referral->getRegisterTimestamp(), 0),
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
     * Update a referral when the prospect joins rewards program
     * @param Referral $referral
     * @return bool
     * @throws \Exception
     */
    public function update(Referral $referral)
    {
        if (!$referral->getReferrerGuid()) {
            throw new \Exception('Referrer GUID is required');
        }

        if (!$referral->getProspectGuid()) {
            throw new \Exception('Prospect GUID is required');
        }

        if (!$referral->getJoinTimestamp()) {
            throw new \Exception('Join timestamp is required');
        }

        $template = "UPDATE referrals SET join_timestamp = ? WHERE referrer_guid = ? AND prospect_guid = ?";

        $values = [
            new Cassandra\Timestamp($referral->getJoinTimestamp(), 0),
            new Cassandra\Bigint($referral->getReferrerGuid()),
            new Cassandra\Bigint($referral->getProspectGuid()),
        ];

        $query = new Prepared\Custom();
        $query->query($template, $values);

        try {
            $success = $this->client->request($query);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Update referral when prospect is notified by the referrer to urge them to join rewards
     * @param Referral $referral
     * @return bool
     * @throws \Exception
     */
    public function ping(Referral $referral)
    {
        if (!$referral->getReferrerGuid()) {
            throw new \Exception('Referrer GUID is required');
        }

        if (!$referral->getProspectGuid()) {
            throw new \Exception('Prospect GUID is required');
        }

        if (!$referral->getPingTimestamp()) {
            throw new \Exception('Ping timestamp is required');
        }

        $template = "UPDATE referrals SET ping_timestamp = ? WHERE referrer_guid = ? AND prospect_guid = ?";

        $values = [
            new Cassandra\Timestamp($referral->getPingTimestamp(), 0),
            new Cassandra\Bigint($referral->getReferrerGuid()),
            new Cassandra\Bigint($referral->getProspectGuid()),
        ];

        $query = new Prepared\Custom();
        $query->query($template, $values);

        try {
            $success = $this->client->request($query);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }


    /**
     * void
     */
    public function delete($referral)
    {
    }
}
