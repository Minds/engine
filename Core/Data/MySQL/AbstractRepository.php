<?php

namespace Minds\Core\Data\MySQL;

use Minds\Core\Config\Config;
use Minds\Core\Log\Logger;
use PDO;
use PDOException;
use ReflectionClass;
use ReflectionException;
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
        Connection $mysqlClientReaderHandler = null,
        Connection $mysqlClientWriterHandler = null,
    ) {
        $this->mysqlClientReader = $this->mysqlHandler->getConnection(Client::CONNECTION_REPLICA);
        $this->mysqlClientReaderHandler = $mysqlClientReaderHandler ?: new Connection($this->mysqlClientReader);

        $this->mysqlClientWriter = $this->mysqlHandler->getConnection(Client::CONNECTION_MASTER);
        $this->mysqlClientWriterHandler = $mysqlClientWriterHandler ?: new Connection($this->mysqlClientWriter);
    }

    /**
     * NOTE: ONLY USE FOR UNIT TESTS
     * @param Client $mysqlHandler
     * @param Config $config
     * @param Logger $logger
     * @param Connection $mysqlClientWriterHandler
     * @param Connection $mysqlClientReaderHandler
     * @return static
     * @throws ReflectionException
     */
    public static function buildForUnitTests(
        Client     $mysqlHandler,
        Config     $config,
        Logger     $logger,
        Connection $mysqlClientWriterHandler,
        Connection $mysqlClientReaderHandler,
    ): static {
        $factory = new ReflectionClass(static::class);
        $instance = $factory->newInstance(
            $mysqlHandler,
            $config,
            $logger,
        );

        $factory->getProperty('mysqlClientWriterHandler')->setValue($instance, $mysqlClientWriterHandler);
        $factory->getProperty('mysqlClientReaderHandler')->setValue($instance, $mysqlClientReaderHandler);

        return $instance;
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
