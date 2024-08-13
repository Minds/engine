<?php

namespace Minds\Core\Boost\V3\Partners;

use Minds\Core\Boost\V3\Enums\BoostStatus;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOException;
use Selective\Database\Connection;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class Repository
{
    private PDO $mysqlClientReader;
    private PDO $mysqlClientWriter;
    private Connection $mysqlClientWriterHandler;
    private Connection $mysqlClientReaderHandler;

    private Logger $logger;

    /**
     * @param MySQLClient|null $mysqlHandler
     * @throws ServerErrorException
     */
    public function __construct(
        private ?MySQLClient $mysqlHandler = null,
    ) {
        $this->mysqlHandler ??= Di::_()->get("Database\MySQL\Client");
        $this->mysqlClientReader = $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_REPLICA);
        $this->mysqlClientReaderHandler = new Connection($this->mysqlClientReader);

        $this->mysqlClientWriter = $this->mysqlHandler->getConnection(MySQLClient::CONNECTION_MASTER);
        $this->mysqlClientWriterHandler = new Connection($this->mysqlClientWriter);

        $this->logger = Di::_()->get('Logger');
    }

    public function beginTransaction(): void
    {
        if ($this->mysqlClientWriter->inTransaction()) {
            throw new PDOException("Cannot initiate transaction. Previously initiated transaction still in progress.");
        }

        $this->mysqlClientWriter->beginTransaction();
    }

    public function rollbackTransaction(): void
    {
        if ($this->mysqlClientWriter->inTransaction()) {
            $this->mysqlClientWriter->rollBack();
        }
    }

    public function commitTransaction(): void
    {
        $this->mysqlClientWriter->commit();
    }

    /**
     * Add boost partner view entry in database
     * @param string $userGuid
     * @param string $boostGuid
     * @param int|null $lastViewedTimestamp
     * @return bool
     */
    public function add(
        string $userGuid,
        string $boostGuid,
        ?int $lastViewedTimestamp = null
    ): bool {
        $this->logger->info("Preparing insert query");

        $statement = $this->mysqlClientWriterHandler
            ->insert()
            ->into('boost_partner_views')
            ->set([
                'served_by_user_guid' => new RawExp(':user_guid'),
                'boost_guid' => new RawExp(':boost_guid'),
                'views' => 1,
                'view_date' => new RawExp(":view_date")
            ])
            ->onDuplicateKeyUpdate([
                'views' => new RawExp('views + 1')
            ])
            ->prepare();

        $this->logger->info("Finished preparing insert query", [$statement->queryString]);

        $values = [
            'user_guid' => $userGuid,
            'boost_guid' => $boostGuid,
            'view_date' => date('Y-m-d 00:00:00', $lastViewedTimestamp),
        ];

        $this->logger->info("Binding insert query parameters");

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $this->logger->info("Completed binding insert query parameters");

        try {
            $statement->execute();
        } catch (PDOException $e) {
            $this->logger->error("Query error details: ", $statement->errorInfo());
            return false;
        }

        $this->logger->info("Completed running insert query");


        return true;
    }

    /**
     * Get revenue details for boost partners in the given time window
     * @param int $fromTimestamp
     * @param int|null $toTimestamp
     * @return iterable
     */
    public function getCPMs(int $fromTimestamp, ?int $toTimestamp = null): iterable
    {
        $values = [];
        // Sub-query
        $completedBoostsQuery = $this->mysqlClientReaderHandler->select()
            ->columns([
                'boosts.tenant_id',
                'boosts.guid',
                'boosts.payment_method',
                'boosts.payment_amount',
                'total_views' => new RawExp('SUM(s.views)')
            ])
            ->from('boosts')
            ->innerJoin(['s' => 'boost_summaries'], 's.guid', Operator::EQ, 'boosts.guid')
            ->where('status', Operator::EQ, BoostStatus::COMPLETED)
            ->where('completed_timestamp', Operator::GTE, new RawExp(':from_timestamp'))
            ->groupBy('boosts.tenant_id', 'boosts.guid');

        $values['from_timestamp'] = date('c', $fromTimestamp);

        if ($toTimestamp) {
            $completedBoostsQuery
                ->where('completed_timestamp', Operator::LTE, new RawExp(':to_timestamp'));
            $values['to_timestamp'] = date('c', $toTimestamp);
        }

        $completedBoostsQuery->alias('completed');

        // Main query
        $query = $this->mysqlClientReaderHandler
            ->select()
            ->columns([
                'served_by_user_guid',
                'cash_total_views_served' => new RawExp('SUM(CASE WHEN completed.payment_method = 1 THEN bpv.views ELSE 0 END)'),
                'tokens_total_views_served' => new RawExp('SUM(CASE WHEN completed.payment_method = 2 THEN bpv.views ELSE 0 END)'),
                'cash_ecpm' => new RawExp('SUM( IF(completed.payment_method = 1, completed.payment_amount, 0)) / SUM(IF(completed.payment_method = 1, completed.total_views, 0)) * 1000'),
                'tokens_ecpm' => new RawExp('SUM(IF(completed.payment_method = 2, completed.payment_amount, 0)) / SUM(IF(completed.payment_method = 2, completed.total_views, 0)) * 1000'),
            ])
            ->from(new RawExp('boost_partner_views as bpv'))
            ->innerJoin(
                new RawExp(rtrim($completedBoostsQuery->build(), ';')),
                'completed.guid',
                Operator::EQ,
                'bpv.boost_guid'
            );

        $query->groupBy('served_by_user_guid');

        $statement = $query->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        try {
            $statement->execute();
        } catch (PDOException $e) {
            $this->logger->info('Error when running query', [
                'error' => $e->getMessage(),
                'query' => $statement->queryString,
                'params' => $values
            ]);
            return [];
        }

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $user_revenue) {
            yield $user_revenue;
        }
    }
}
