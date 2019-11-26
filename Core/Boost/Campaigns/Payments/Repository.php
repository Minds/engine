<?php

namespace Minds\Core\Boost\Campaigns\Payments;

use Cassandra\Bigint;
use Cassandra\Decimal;
use Cassandra\Timestamp;
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
    ) {
        $this->db = $db ?: Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * @param array $opts
     * @return Response
     */
    public function getList(array $opts = [])
    {
        $opts = array_merge([
            'owner_guid' => null,
            'campaign_guid' => null,
            'tx' => null,
        ], $opts);

        $cql = "SELECT * FROM boost_campaigns_payments";
        $where = [];
        $values = [];

        if ($opts['owner_guid']) {
            $where[] = 'owner_guid = ?';
            $values[] = new Bigint($opts['owner_guid']);
        }

        if ($opts['campaign_guid']) {
            $where[] = 'campaign_guid = ?';
            $values[] = new Bigint($opts['campaign_guid']);
        }

        if ($opts['tx']) {
            $where[] = 'tx = ?';
            $values[] = (string) $opts['tx'];
        }

        if ($where) {
            $cql .= sprintf(" WHERE %s", implode(' AND ', $where));
        }

        $prepared = new Custom();
        $prepared->query($cql, $values);

        $response = new Response();

        try {
            // TODO: Use Cassandra Scroll for getList
            $rows = $this->db->request($prepared) ?: [];

            foreach ($rows as $row) {
                $payment = new Payment();
                $payment
                    ->setOwnerGuid($row['owner_guid']->toInt())
                    ->setCampaignGuid($row['campaign_guid']->toInt())
                    ->setTx($row['tx'])
                    ->setSource($row['source'])
                    ->setAmount($row['amount']->toDouble())
                    ->setTimeCreated($row['time_created']->time());

                $response[] = $payment;
            }
        } catch (Exception $e) {
            $response->setException($e);
        }

        return $response;
    }

    /**
     * @param Payment $payment
     * @return bool
     */
    public function add(Payment $payment)
    {
        $cql = "INSERT INTO boost_campaigns_payments (owner_guid, campaign_guid, tx, source, amount, time_created) VALUES (?, ?, ?, ?, ?, ?)";
        $values = [
            new Bigint($payment->getOwnerGuid()),
            new Bigint($payment->getCampaignGuid()),
            (string) $payment->getTx(),
            (string) $payment->getSource(),
            new Decimal($payment->getAmount()),
            new Timestamp($payment->getTimeCreated())
        ];

        $prepared = new Custom();
        $prepared->query($cql, $values);

        return (bool) $this->db->request($prepared, true);
    }

    /**
     * @param Payment $payment
     * @return bool
     */
    public function update(Payment $payment)
    {
        return $this->add($payment);
    }

    /**
     * @param Payment $payment
     * @throws NotImplementedException
     */
    public function delete(Payment $payment)
    {
        throw new NotImplementedException();
    }
}
