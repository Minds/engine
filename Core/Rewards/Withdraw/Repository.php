<?php


namespace Minds\Core\Rewards\Withdraw;

use Cassandra\Varint;
use Cassandra\Timestamp;
use Exception;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Di\Di;
use Minds\Core\Util\BigNumber;

class Repository
{
    /** @var Client */
    protected $db;

    /**
     * Repository constructor.
     * @param Client $db
     */
    public function __construct($db = null)
    {
        $this->db = $db ? $db : Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * @param array $opts
     * @return array
     */
    public function getList(array $opts): array
    {
        $opts = array_merge([
            'status' => null,
            'user_guid' => null,
            'from' => null,
            'to' => null,
            'completed' => null,
            'completed_tx' => null,
            'limit' => 12,
            'offset' => null,
        ], $opts);

        $cql = "SELECT * from withdrawals";
        $where = [];
        $values = [];

        if ($opts['status']) {
            $cql = "SELECT * from withdrawals_by_status";
            $where[] = 'status = ?';
            $values[] = (string) $opts['status'];
        }

        if ($opts['user_guid']) {
            $where[] = 'user_guid = ?';
            $values[] = new Varint($opts['user_guid']);
        }

        if ($opts['timestamp']) {
            $where[] = 'timestamp = ?';
            $values[] = new Timestamp($opts['timestamp']);
        }

        if ($opts['tx']) {
            $where[] = 'tx = ?';
            $values[] = (string) $opts['tx'];
        }

        if ($opts['from']) {
            $where[] = 'timestamp >= ?';
            $values[] = new Timestamp($opts['from']);
        }

        if ($opts['to']) {
            $where[] = 'timestamp <= ?';
            $values[] = new Timestamp($opts['to']);
        }

        if ($opts['completed']) {
            $where[] = 'completed = ?';
            $values[] = (string) $opts['completed'];
        }

        if ($where) {
            $cql .= " WHERE " . implode(" AND ", $where);
        }

        $query = new Custom();
        $query->query($cql, $values);
        $query->setOpts([
            'page_size' => (int) $opts['limit'],
            'paging_state_token' => base64_decode($opts['offset'], true),
        ]);

        try {
            $rows = $this->db->request($query);

            $requests = [];
            foreach ($rows ?: [] as $row) {
                $request = new Request();
                $request
                    ->setUserGuid((string) $row['user_guid']->value())
                    ->setTimestamp($row['timestamp']->time())
                    ->setTx($row['tx'])
                    ->setAddress($row['address'] ?: '')
                    ->setAmount((string) BigNumber::_($row['amount']))
                    ->setCompleted((bool) $row['completed'])
                    ->setCompletedTx($row['completed_tx'] ?: null)
                    ->setGas((string) BigNumber::_($row['gas']))
                    ->setStatus($row['status'] ?: '')
                ;

                $requests[] = $request;
            }

            return [
                'withdrawals' => $requests,
                'token' => $rows->pagingStateToken(),
            ];
        } catch (Exception $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function add(Request $request)
    {
        $cql = "INSERT INTO withdrawals (user_guid, timestamp, tx, address, amount, completed, completed_tx, gas, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $values = [
            new Varint($request->getUserGuid()),
            new Timestamp($request->getTimestamp()),
            $request->getTx(),
            (string) $request->getAddress(),
            new Varint($request->getAmount()),
            (bool) $request->isCompleted(),
            ((string) $request->getCompletedTx()) ?: null,
            new Varint($request->getGas()),
            (string) $request->getStatus(),
        ];

        $prepared = new Custom();
        $prepared->query($cql, $values);

        return $this->db->request($prepared, true);
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function update(Request $request)
    {
        return $this->add($request);
    }

    /**
     * @param Request $request
     * @throws Exception
     */
    public function delete(Request $request)
    {
        throw new Exception('Not allowed');
    }
}
