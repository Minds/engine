<?php
namespace Minds\Core\Data\MySQL;

use Minds\Core\Config\Config;
use Minds\Core\Log\Logger;
use PDO;
use PDOException;
use Selective\Database\Connection;

abstract class AbstractRepository
{
    protected PDO $mysqlClientReader;
    protected PDO $mysqlClientWriter;
    protected Connection $mysqlClientWriterHandler;
    protected Connection $mysqlClientReaderHandler;

    /**
     * @param Client $mysqlHandler
     * @throws ServerErrorException
     */
    public function __construct(
        protected Client $mysqlHandler,
        protected Config $config,
        protected Logger $logger,
    ) {
        $this->mysqlClientReader = $this->mysqlHandler->getConnection(Client::CONNECTION_REPLICA);
        $this->mysqlClientReaderHandler = new Connection($this->mysqlClientReader);

        $this->mysqlClientWriter = $this->mysqlHandler->getConnection(Client::CONNECTION_MASTER);
        $this->mysqlClientWriterHandler = new Connection($this->mysqlClientWriter);
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
}
