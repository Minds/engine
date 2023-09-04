<?php
declare(strict_types=1);

namespace Minds\Core\Monetization\Partners;

use Minds\Common\Repository\Response;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Data\MySQL\Client;
use Minds\Core\Log\Logger;
use Minds\Exceptions\ServerErrorException;
use PDO;
use Selective\Database\Connection;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class RelationalRepository extends AbstractRepository
{
    public function __construct(
        Client $mysqlHandler,
        Logger $logger,
        ?Connection $mysqlClientReaderHandler = null,
        ?Connection $mysqlClientWriterHandler = null
    ) {
        parent::__construct($mysqlHandler, $logger);

        $this->mysqlClientReaderHandler = $mysqlClientReaderHandler ?? $this->mysqlClientReaderHandler;
        $this->mysqlClientWriterHandler = $mysqlClientWriterHandler ?? $this->mysqlClientWriterHandler;
    }

    // TODO: Refactor to getBalancesPerUser
    /**
     * @param int|null $from
     * @param int|null $to
     * @param int|null $userGuid
     * @param int|null $offset
     * @return Response
     * @throws ServerErrorException
     */
    public function getList(
        ?int $from = null,
        ?int $to = null,
        ?int $userGuid = null,
        ?int $offset = null
    ): Response {
        $statement = $this->mysqlClientReaderHandler->select()
            ->columns([
                'timestamp',
                'item',
                'user_guid',
                'amount_cents',
                'amount_tokens',
            ])
            ->from('minds_partner_earnings');
        $values = [];

        if ($from) {
            $statement->where('timestamp', Operator::GTE, new RawExp(':from'));
            $values['from'] = date('c', $from);
        }

        if ($to) {
            $statement->where('timestamp', Operator::LT, new RawExp(':to'));
            $values['to'] = date('c', $to);
        }

        if ($userGuid) {
            $statement->where('user_guid', Operator::EQ, new RawExp(':user_guid'));
            $values['user_guid'] = $userGuid;
        }

        $statement->orderBy('timestamp DESC');

        // TODO: Consider options for pagination

        $statement = $statement->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();
        if ($statement->rowCount() === 0) {
            return (new Response([]))->setLastPage(true);
        }

        $response = new Response();

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $response[] = (new EarningsDeposit())
                ->setTimestamp(strtotime($row['timestamp']))
                ->setItem($row['item'])
                ->setUserGuid((string) $row['user_guid'])
                ->setAmountCents($row['amount_cents'])
                ->setAmountTokens($row['amount_tokens'] ?? 0);
        }

        $response->setLastPage(true);
        $response->setPagingToken(null);
        return $response;
    }

    /**
     * @param int|null $fromTimestamp
     * @param int|null $toTimestamp
     * @return iterable
     */
    public function getBalancesPerUser(
        ?int $fromTimestamp = null,
        ?int $toTimestamp = null
    ): iterable {
        $statement = $this->mysqlClientReaderHandler->select()
            ->from('minds_partner_earnings')
            ->columns([
                'user_guid',
                new RawExp('SUM(amount_cents) as total_cents')
            ])
            ->groupBy('user_guid');
        $values = [];

        if ($fromTimestamp) {
            $statement->where('timestamp', Operator::GTE, new RawExp(':from'));
            $values['from'] = date('c', $fromTimestamp);
        }
        if ($toTimestamp) {
            $statement->where('timestamp', Operator::LTE, new RawExp(':to'));
            $values['to'] = date('c', $toTimestamp);
        }

        $statement = $statement->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $deposit) {
            yield (new EarningsBalance())
                ->setUserGuid($deposit['user_guid'])
                ->setAmountCents((int) $deposit['total_cents']);
        }
    }

    /**
     * @param EarningsDeposit $deposit
     * @return bool
     */
    public function add(EarningsDeposit $deposit): bool
    {
        $statement = $this->mysqlClientWriterHandler->insert()
            ->into('minds_partner_earnings')
            ->set([
                'user_guid' => (int) $deposit->getUserGuid(),
                'timestamp' => date('c', $deposit->getTimestamp()),
                'item' => $deposit->getItem(),
                'amount_cents' => $deposit->getAmountCents() ?? null,
                'amount_tokens' => $deposit->getAmountTokens() ?? null,
            ])
            ->onDuplicateKeyUpdate([
                'amount_cents' => $deposit->getAmountCents() ?? null,
                'amount_tokens' => $deposit->getAmountTokens() ?? null,
            ])
            ->prepare();

        $statement->execute();
        return (bool) $statement->rowCount();
    }

    /**
     * @param int $userGuid
     * @param int|null $asOfTs
     * @return EarningsBalance
     * @throws ServerErrorException
     */
    public function getBalance(int $userGuid, ?int $asOfTs = null): EarningsBalance
    {
        $statement = $this->mysqlClientReaderHandler->select()
            ->from('minds_partner_earnings')
            ->columns([
                new RawExp('SUM(amount_cents) AS cents'),
                new RawExp('SUM(amount_tokens) AS tokens'),
            ])
            ->where('user_guid', Operator::EQ, new RawExp(':user_guid'))
            ->where('timestamp', Operator::LTE, new RawExp(':timestamp'))
            ->prepare();
        $values = [
            'user_guid' => $userGuid,
            'timestamp' => date('c', $asOfTs ?? time()),
        ];
        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return (new EarningsBalance())
            ->setUserGuid((string) $userGuid)
            ->setAmountCents($row['cents'])
            ->setAmountTokens($row['tokens']);
    }

    /**
     * @param int $userGuid
     * @param array $items
     * @param int|null $asOfTs
     * @return EarningsBalance
     * @throws ServerErrorException
     */
    public function getBalanceByItem(int $userGuid, array $items, ?int $asOfTs = null): EarningsBalance
    {
        $statement = $this->mysqlClientReaderHandler->select()
            ->from('minds_partner_earnings')
            ->columns([
                new RawExp('SUM(amount_cents) AS cents'),
                new RawExp('SUM(amount_tokens) AS tokens'),
            ])
            ->where('user_guid', Operator::EQ, new RawExp(':user_guid'))
            ->where('timestamp', Operator::LTE, new RawExp(':timestamp'))
            ->where('item', Operator::IN, $items)
            ->prepare();

        $values = [
            'user_guid' => $userGuid,
            'timestamp' => date('c', $asOfTs ?? time()),
        ];

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return (new EarningsBalance())
            ->setUserGuid((string) $userGuid)
            ->setAmountCents($row['cents'])
            ->setAmountTokens($row['tokens']);
    }
}
