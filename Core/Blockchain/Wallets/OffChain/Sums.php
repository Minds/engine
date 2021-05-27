<?php
namespace Minds\Core\Blockchain\Wallets\OffChain;

use Cassandra;
use Cassandra\Varint;
use Cassandra\Timestamp;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Di\Di;
use Minds\Core\Util\BigNumber;
use Minds\Entities\User;

class Sums
{
    /** @var Client */
    private $db;

    /** @var User */
    private $user;

    /** @var User */
    private $receiver;

    /** @var int $timestamp */
    private $timestamp;

    public function __construct($db = null)
    {
        $this->db = $db ? $db : Di::_()->get('Database\Cassandra\Cql');
    }

    public function setTimestamp($ts)
    {
        $this->timestamp = $ts;
        return $this;
    }

    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    public function setReceiver($receiver)
    {
        $this->receiver = $receiver;
        return $this;
    }

    /**
     * Get the balance
     */
    public function getBalance()
    {
        $query = new Custom();

        if ($this->user) {
            $query->query(
                "SELECT 
                SUM(amount) as balance 
                FROM blockchain_transactions_mainnet_by_address
                WHERE user_guid = ?
                AND wallet_address = 'offchain'",
                [
                    new Varint((int) $this->user->guid)
                ]
            );
        // $query->setOpts([
            //     'consistency' => \Cassandra::CONSISTENCY_ALL
            // ]);
        } else {
            //$query->query("SELECT SUM(amount) as balance from rewards");
        }

        try {
            $rows = $this->db->request($query);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return 0;
        }

        if (!$rows) {
            return 0;
        }
        
        return (string) BigNumber::_($rows[0]['balance']);
    }


    public function getContractBalance($contract = '', $onlySpend = false)
    {
        if (!$this->user) {
            return 0;
        }
        $cql = "SELECT SUM(amount) as balance from blockchain_transactions_mainnet WHERE user_guid = ? AND wallet_address = ?";
        $values = [
            new Varint($this->user->guid),
            'offchain',
        ];

        if ($this->timestamp) {
            $cql .= " AND timestamp >= ?";
            $values[] = new Timestamp($this->timestamp);
        }

        if ($contract) {
            $cql .= " AND contract = ?";
            $values[] = (string) $contract;
        }

        if ($onlySpend) {
            $cql .= " AND amount < 0 ALLOW FILTERING";
        }

        $query = new Custom();
        $query->query($cql, $values);

        try {
            $rows = $this->db->request($query);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return 0;
        }

        if (!$rows) {
            return 0;
        }

        return (string) BigNumber::_($rows[0]['balance']);
    }

    /**
     * Return a count of the transactions
     * @return int
     */
    public function getCount(): int
    {
        $query = new Custom();

        if ($this->user) {
            $query->query(
                "SELECT COUNT(*) as count
                FROM blockchain_transactions_mainnet_by_address
                WHERE user_guid = ?
                AND wallet_address = 'offchain'",
                [
                    new Varint((int) $this->user->guid)
                ]
            );
        // $query->setOpts([
            //     'consistency' => \Cassandra::CONSISTENCY_ALL
            // ]);
        } else {
            return 0;
        }

        try {
            $rows = $this->db->request($query);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return 0;
        }

        if (!$rows) {
            return 0;
        }
        
        return (string) BigNumber::_($rows[0]['count']);
    }

    public function countByReceiver(): BigNumber
    {
        $query = new Custom();

        if ($this->user) {
            $cql = "SELECT *
                FROM blockchain_transactions_mainnet_by_address
                WHERE user_guid = ?
                AND wallet_address = 'offchain'";
            $values = [
                new Varint((int) $this->user->guid)
            ];

            if ($this->timestamp) {
                $cql .= " AND timestamp >= ?";
                $values[] = new Timestamp($this->timestamp, 0);
            }

            $query->query(
                $cql,
                $values
            );
        } else {
            return 0;
        }

        try {
            $rows = $this->db->request($query);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return 0;
        }

        if (!$rows) {
            return 0;
        }

        $agg = BigNumber::_(0);

        foreach ($rows as $tx) {
            $txData = json_decode($tx['data'] ?? false);
            // TODO: Filter out subscriptions?
            if ($txData->receiver_guid === (string) $this->receiver->getGuid()) {
                $agg = $agg->add($txData->amount);
            }
        }

        return $agg;
    }
}
