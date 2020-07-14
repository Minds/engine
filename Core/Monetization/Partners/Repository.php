<?php
namespace Minds\Core\Monetization\Partners;

use Minds\Core\Data\Cassandra\Client;
use Minds\Core\Data\Cassandra\Prepared\Custom as Prepared;
use Minds\Common\Repository\Response;
use Minds\Core\Di\Di;
use Cassandra\Bigint;
use Cassandra\Timestamp;

class Repository
{
    /** @var Client */
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?? Di::_()->get('Database\Cassandra\Cql');
    }

    /**
     * @param array $opts
     * @return Response
     */
    public function getList(array $opts = []): Response
    {
        $opts = array_merge([
            'from' => null,
            'to' => null,
            'user_guid' => null,
            'allow_filtering' => false,
        ], $opts);

        $statement = "SELECT * FROM partner_earnings_ledger";
        $values = [];

        $where = [];

        if ($opts['user_guid']) {
            $where[] = "user_guid = ?";
            $values[] = new Bigint($opts['user_guid']);
        }

        if ($opts['from']) {
            $where[] = "timestamp >= ?";
            $values[] = new Timestamp($opts['from']);
            if (!$opts['user_guid']) { // This is a temporary work around (MH)
                $opts['allow_filtering'] = true;
            }
        }

        if ($opts['to']) {
            $where[] = "timestamp < ?";
            $values[] = new Timestamp($opts['to']);
        }

        $statement .= " WHERE " . implode(' AND ', $where);

        if ($opts['allow_filtering']) {
            $statement .= " ALLOW FILTERING";
        }

        $prepared = new Prepared();
        $prepared->query($statement, $values);

        $result = $this->db->request($prepared);

        if (!$result || !$result[0]) {
            return (new Response())->setLastPage(true);
        }

        $response = new Response();

        foreach ($result as $row) {
            $deposit = new EarningsDeposit();
            $deposit->setTimestamp($row['timestamp']->time())
                ->setItem($row['item'])
                ->setUserGuid((string) $row['user_guid'])
                ->setAmountCents($row['amount_cents'])
                ->setAmountTokens($row['amount_tokens'] ? $row['amount_tokens']->value() : 0);
            $response[] = $deposit;
        }

        $response->setLastPage($result->isLastPage());
        return $response;
    }

    /**
     * @param string $urn
     * @return EarningsDeposit
     */
    public function get($urn): EarningsDeposit
    {
        // TODO
    }

    /**
     * @param EarningsDeposit $deposit
     * @return bool
     */
    public function add(EarningsDeposit $deposit): bool
    {
        $prepared = new Prepared();
        $prepared->query(
            "INSERT INTO partner_earnings_ledger (user_guid, timestamp, item, amount_cents, amount_tokens) VALUES (?,?,?,?,?)",
            [
                new Bigint($deposit->getUserGuid()),
                new Timestamp($deposit->getTimestamp()),
                $deposit->getItem(),
                $deposit->getAmountCents() ? (int) $deposit->getAmountCents() : null,
                $deposit->getAmountTokens() ? new Bigint($deposit->getAmountTokens()) : null,
            ]
        );
        return (bool) $this->db->request($prepared);
    }

    /**
     * @param EarningsDeposit $deposit
     * @param array $fields
     * @return bool
     */
    public function update(EarningsDeposit $deposit, $fields = []): bool
    {
    }

    /**
     * @param EarningsDeposit $deposit
     * @return bool
     */
    public function delete(EarningsDeposit $deposit): bool
    {
    }

    /**
     * @param string $guid
     * @return EarningsBalance
     */
    public function getBalance($guid): EarningsBalance
    {
        $statement = "SELECT SUM(amount_cents) as cents, SUM(amount_tokens) as tokens
			FROM partner_earnings_ledger
			WHERE user_guid = ?
		";
        $values = [
             new Bigint($guid)
        ];

        $prepared = new Prepared();
        $prepared->query($statement, $values);

        $result = $this->db->request($prepared);
        $row = $result[0];
        
        $balance = new EarningsBalance();
        $balance->setUserGuid($guid)
            ->setAmountCents($row['cents'])
            ->setAmountTokens($row['tokens']->value());
        return $balance;
    }
}
