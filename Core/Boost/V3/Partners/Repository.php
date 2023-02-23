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

    public function add(
        string $userGuid,
        string $boostGuid,
        ?int $lastViewedTimestamp = null
    ): bool {
        $this->logger->addInfo("Preparing insert query");

        $statement = $this->mysqlClientWriterHandler
            ->insert()
            ->into('boosts_partner_views')
            ->set([
                'served_by_user_guid' => new RawExp(':user_guid'),
                'boost_guid' => new RawExp(':boost_guid'),
                'views' => 1,
                'last_viewed_timestamp' => new RawExp(":last_viewed_timestamp")
            ])
            ->onDuplicateKeyUpdate([
                'views' => new RawExp('views + 1'),
                'last_viewed_timestamp' => new RawExp(":last_viewed_timestamp")
            ])
            ->prepare();

        $this->logger->addInfo("Finished preparing insert query", [$statement->queryString]);

        $values = [
            'user_guid' => $userGuid,
            'boost_guid' => $boostGuid,
            'last_viewed_timestamp' => date('c', $lastViewedTimestamp),
        ];

        $this->logger->addInfo("Binding insert query parameters");

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        $this->logger->addInfo("Completed binding insert query parameters");

        try {
            $statement->execute();
        } catch (PDOException $e) {
            $this->logger->addError("Query error details: ", $statement->errorInfo());
            return false;
        }

        $this->logger->addInfo("Completed running insert query");


        return true;
    }

    /**
     * @param int $fromTimestamp
     * @param int|null $toTimestamp
     * @return iterable
     */
    public function getCPMs(int $fromTimestamp, ?int $toTimestamp = null): iterable
    {
        $query = $this->mysqlClientReaderHandler
            ->select()
            ->columns([
                'served_by_user_guid',
                'total_views_served' => new RawExp('SUM(views)'),
                'ecpm' => new RawExp('SUM((payment_amount / views) * 1000)'),
            ])
            ->from('boosts_partner_views')
            ->innerJoin('boosts', 'guid', Operator::EQ, 'boost_guid')
            ->where('updated_timestamp', Operator::GTE, new RawExp(':from_timestamp'))
            ->where('status', Operator::EQ, BoostStatus::COMPLETED);

        $values = [
            'from_timestamp' => $fromTimestamp
        ];

        if ($toTimestamp) {
            $query
                ->where('updated_timestamp', Operator::LTE, new RawExp(':to_timestamp'));
            $values['to_timestamp'] = $toTimestamp;
        }

        $query->groupBy('served_by_user_guid');

        $statement = $query->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($statement, $values);

        try {
            $statement->execute();
        } catch (PDOException $e) {
            $this->logger->addInfo('Error when running query', [
                'error' => $e->getMessage(),
                'query' => $statement->queryString,
                'params' => $statement->debugDumpParams()
            ]);
            return;
        }

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $user_revenue) {
            yield $user_revenue;
        }
    }
}
