<?php

namespace Spec\Minds\Core\Security\Rbac;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Security\Rbac\Repository;
use PhpSpec\ObjectBehavior;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Di\Di;
use PDO;

class RepositorySpec extends ObjectBehavior
{
    private $mysqlClientMock;
    private $mysqlMasterMock;
    private $mysqlReplicaMock;

    public function let(Config $configMock, MySQLClient $mysqlClientMock, PDO $mysqlMasterMock, PDO $mysqlReplicaMock)
    {
        $this->beConstructedWith($configMock, $mysqlClientMock, Di::_()->get('Logger'));
        $this->mysqlClientMock = $mysqlClientMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::MASTER)
            ->willReturn($mysqlMasterMock);
        $this->mysqlMasterMock = $mysqlMasterMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::REPLICA)
            ->willReturn($mysqlReplicaMock);
        $this->mysqlReplicaMock = $mysqlReplicaMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }
}
