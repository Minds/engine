<?php
namespace Minds\Core\Rewards\Contributions;

use Cassandra;
use Cassandra\Varint;
use Cassandra\Timestamp;
use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Scroll;
use Minds\Core\Data\Cassandra\Prepared\Custom;
use Minds\Core\Di\Di;
use Minds\Core\Util\BigNumber;

class Repository
{
    /** @var Client */
    private $db;

    /** @var Scroll */
    private $scroll;

    public function __construct($db = null, $scroll = null)
    {
        $this->db = $db ? $db : Di::_()->get('Database\Cassandra\Cql');
        $this->scroll = $scroll ? $scroll : Di::_()->get('Database\Cassandra\Cql\Scroll');
    }

    public function add($contributions)
    {
        if (!is_array($contributions)) {
            $contributions = [ $contributions ];
        }

        $requests = [];
        $template = "INSERT INTO contributions (
            timestamp,
            user_guid,
            metric,
            amount,
            score
            ) 
            VALUES (?,?,?,?,?)";
        foreach ($contributions as $contribution) {
            $requests[] = [
                'string' => $template,
                'values' => [
                    new Timestamp($contribution->getTimestamp() / 1000, 0),
                    new Varint($contribution->getUser()->guid),
                    $contribution->getMetric(),
                    new Varint($contribution->getAmount()),
                    new Varint($contribution->getScore())
                ]
            ];
        }

        $this->db->batchRequest($requests, Cassandra::BATCH_UNLOGGED);

        return $this;
    }

    public function getList($options)
    {
        $options = array_merge([
            'user_guid' => null,
            'from' => null,
            'to' => null,
            'type' => '',
            'limit' => 1000,
            'offset' => null
        ], $options);


        $cql = "SELECT * from contributions";
        $where = [];
        $values = [];

        if ($options['user_guid']) {
            $where[] = 'user_guid = ?';
            $values[] = new Varint($options['user_guid']);
        }

        if ($options['from']) {
            $where[] = 'timestamp >= ?';
            $values[] = new Timestamp($options['from'], 0);
        }

        if ($options['to']) {
            $where[] = 'timestamp <= ?';
            $values[] = new Timestamp($options['to'], 0);
        }

        if ($options['type']) {
            $where[] = 'metric = ?';
            $values[] = (string) $options['type'];
        }

        if ($where) {
            $cql .= " WHERE " . implode(" AND ", $where);
        }

        $query = new Custom();
        $query->query($cql, $values);
        $query->setOpts([
            'page_size' => (int) $options['limit'],
            'paging_state_token' => base64_decode($options['offset'], true)
        ]);

        $contributions = [];

        try {
            $rows = $this->db->request($query);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return $contributions;
        }

        foreach ($rows as $row) {
            $contribution = new Contribution();
            $contribution
                ->setUser((string) $row['user_guid'])
                ->setMetric((string) $row['metric'])
                ->setTimestamp($row['timestamp']->time() * 1000)
                ->setAmount((string) BigNumber::_($row['amount']))
                ->setScore((int) $row['score']);

            $contributions[] = $contribution;
        }
        $pagingStateToken = $rows ? $rows->pagingStateToken() : null;
        return [
            'contributions' => $contributions,
            'token' => $pagingStateToken
        ];
    }

    public function update($key, $guids)
    {
        // TODO: Implement update() method.
    }

    public function delete($entity)
    {
        // TODO: Implement delete() method.
    }

    public function sum($options)
    {
    }

    /**
     * @param ContributionQueryOpts $opts
     * @return ContributionSummary[]
     */
    public function getSummaries(ContributionQueryOpts $opts): iterable
    {
        $statement = "SELECT timestamp, user_guid, SUM(score) as score, SUM(amount) as amount
            FROM contributions_by_timestamp
            WHERE timestamp=?";

        $values = [ new Timestamp($opts->getDateTs(), 0) ];

        if ($opts->getUserGuid()) {
            $statement .= " AND user_guid = ?";
            $values[] = new Varint($opts->getUserGuid());
        }

        $statement .= "GROUP BY user_guid";

        $prepared = new Custom();
        $prepared->query($statement, $values);

        foreach ($this->scroll->request($prepared) as $row) {
            $summary = new ContributionSummary();
            $summary->setUserGuid((string) $row['user_guid'])
                ->setDateTs($row['timestamp']->time())
                ->setScore($row['score'])
                ->setAmount($row['amount']);
            yield $summary;
        }
    }
}
