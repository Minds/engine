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
            'referrer_guid' => null,
        ], $opts);

        if (!$opts['referrer_guid']) {
            throw new \Exception('Referrer guid not provided');
        }

        $template = "SELECT * FROM referrals WHERE referrer_guid = ?";
        $values = [ new Bigint($opts['referrer_guid']) ];

        $query = new Prepared\Custom();
        $query->query($template, $values);

        $response = new Response();

        try {
            $result = $this->client->request($query);
            
            foreach ($result as $row) {

                $referral = new Referral(); 

                $referral->setProspectGuid((string) $row['prospect_guid'])
                    ->setReferrerGuid((string) $row['referrer_guid'])
                    ->setRegisterTimestamp((string) $row['register_timestamp'])
                    ->setJoinTimestamp((string) $row['join_timestamp']);

                // OJMTODO: check if joinTimestamp exists, add it to the referral

                $response[] = $referral;
            }

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

        return $success;
    }

    /**
     * void
     */
    public function delete($referral)
    {
    }

}
