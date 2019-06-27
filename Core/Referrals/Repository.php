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

class Repository
{
    /** @var Client $client */
    private $client;

    public function __construct($client = null)
    {
        $this->client = $client ?: Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * Return a list of referrals
     * @param array $opts
     * @return Response
     */
    public function getList($opts = [])
    {
        $opts = array_merge([
            'limit' => 12,
            'offset' => '',
            'referrer_guid' => null,
        ], $opts);

        if (!$opts['referrer_guid']) {
            throw new \Exception('Referrer GUID is required');
        }

        $cqlOpts = [];
        if ($opts['limit']) {
            $cqlOpts['page_size'] = (int) $opts['limit'];
        }

        if ($opts['offset']) {
            $cqlOpts['paging_state_token'] = base64_decode($opts['offset']);
        }

        $template = "SELECT * FROM referrals WHERE referrer_guid = ?";
        $values = [ new Bigint($opts['referrer_guid']) ];

        $query = new Prepared\Custom();
        $query->query($template, $values);
        $query->setOpts($cqlOpts);

        $response = new Response();

        try {
            $rows = $this->client->request($query);

            foreach ($rows as $row) {

                $referral = new Referral(); 

                $referral->setProspectGuid((string) $row['prospect_guid'])
                    ->setReferrerGuid((string) $row['referrer_guid'])
                    ->setRegisterTimestamp((string) $row['register_timestamp'])
                    ->setJoinTimestamp((string) $row['join_timestamp']);

                $response[] = $referral;
            }
            
            $response->setPagingToken(base64_encode($rows->pagingStateToken()));
            $response->setLastPage($rows->isLastPage()); 

        } catch (\Exception $e) {
            // return false;
            $response = $e;
            return $response;
        }

        return $response;
    }


    /**
     * Add a referral
     * @param Referral $referral
     * @return bool
     */
    public function add(Referral $referral)
    {
        if (!$referral->getReferrerGuid()) {
            throw new \Exception('Referrer GUID is required');
        }

        if (!$referral->getProspectGuid()) {
            throw new \Exception('Prospect GUID is required');
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
            new Cassandra\Timestamp($referral->getRegisterTimestamp()),
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
     * Update a referral
     * @param Referral $referral
     * @return bool
     */
    public function update(Referral $referral)
    {
        if (!$referral->getJoinTimestamp()) {
            throw new \Exception('Join timestamp is required');
        }
        
        $template = "UPDATE referrals SET join_timestamp = ? WHERE referrer_guid = ? AND prospect_guid = ?";
        
        $values = [
            new Cassandra\Timestamp($referral->getJoinTimestamp()),
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
