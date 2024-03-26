<?php

namespace Spec\Minds\Core\Feeds\RSS\Repositories;

use Minds\Core\Config\Config;
use Minds\Core\Feeds\RSS\Repositories\RssImportsRepository;
use Minds\Core\Data\MySQL;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class RssImportsRepositorySpec extends ObjectBehavior
{
    private Collaborator $mysqlClientMock;
    private Collaborator $mysqlMasterMock;
    private Collaborator $mysqlReplicaMock;
    private Collaborator $configMock;
    
    public function let(
        MySQL\Client $mysqlClientMock,
        Config $configMock,
        Logger $loggerMock,
        PDO $mysqlMasterMock,
        PDO $mysqlReplicaMock,
    ) {
        $this->beConstructedWith($mysqlClientMock, $configMock, $loggerMock);

        $this->mysqlClientMock = $mysqlClientMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::MASTER)
            ->shouldBeCalled()
            ->willReturn($mysqlMasterMock);
        $this->mysqlMasterMock = $mysqlMasterMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::REPLICA)
            ->shouldBeCalled()
            ->willReturn($mysqlReplicaMock);
        $this->mysqlReplicaMock = $mysqlReplicaMock;

        $this->configMock = $configMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(RssImportsRepository::class);
    }

    public function it_should_add_entry(PDOStatement $stmtMock)
    {
        $guid = Guid::build();

        $this->mysqlMasterMock->prepare(Argument::any())
            ->willReturn($stmtMock);

        $stmtMock->execute([
            'tenant_id' => -1,
            'feed_id' => 1,
            'url' => 'https://php.spec/blog/blog-1',
            'activity_guid' => $guid,
        ])
            ->willReturn(true);

        $this->addEntry(1, 'https://php.spec/blog/blog-1', $guid)
            ->shouldBe(true);
    }

    public function it_should_add_entry_for_tenant(PDOStatement $stmtMock)
    {
        $this->configMock->get('tenant_id')
            ->willReturn(1);
    
        $guid = Guid::build();

        $this->mysqlMasterMock->prepare(Argument::any())
            ->willReturn($stmtMock);

        $stmtMock->execute([
            'tenant_id' => 1,
            'feed_id' => 1,
            'url' => 'https://php.spec/blog/blog-1',
            'activity_guid' => $guid,
        ])
            ->willReturn(true);

        $this->addEntry(1, 'https://php.spec/blog/blog-1', $guid)
            ->shouldBe(true);
    }

    public function it_should_return_match_exists(PDOStatement $stmtMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())
            ->willReturn($stmtMock);

        $stmtMock->execute([
            'tenant_id' => -1,
            'feed_id' => 1,
            'url' => 'https://php.spec/blog/blog-1'
        ])->shouldBeCalled();

        $stmtMock->rowCount()->willReturn(1);

        $this->hasMatch(1, 'https://php.spec/blog/blog-1')
            ->shouldBe(true);
    }

    
    public function it_should_return_match_exists_for_tenant(PDOStatement $stmtMock)
    {
        $this->configMock->get('tenant_id')
            ->willReturn(1);

        $this->mysqlReplicaMock->prepare(Argument::any())
            ->willReturn($stmtMock);

        $stmtMock->execute([
            'tenant_id' => 1,
            'feed_id' => 1,
            'url' => 'https://php.spec/blog/blog-1'
        ])->shouldBeCalled();

        $stmtMock->rowCount()->willReturn(1);

        $this->hasMatch(1, 'https://php.spec/blog/blog-1')
            ->shouldBe(true);
    }

    public function it_should_return_match_doesnt_exists(PDOStatement $stmtMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())
            ->willReturn($stmtMock);

        $stmtMock->execute([
            'tenant_id' => -1,
            'feed_id' => 1,
            'url' => 'https://php.spec/blog/blog-1'
        ])->shouldBeCalled();

        $stmtMock->rowCount()->willReturn(0);

        $this->hasMatch(1, 'https://php.spec/blog/blog-1')
            ->shouldBe(false);
    }

    
    public function it_should_return_match_doesnt_exists_for_tenant(PDOStatement $stmtMock)
    {
        $this->configMock->get('tenant_id')
            ->willReturn(1);

        $this->mysqlReplicaMock->prepare(Argument::any())
            ->willReturn($stmtMock);

        $stmtMock->execute([
            'tenant_id' => 1,
            'feed_id' => 1,
            'url' => 'https://php.spec/blog/blog-1'
        ])->shouldBeCalled();

        $stmtMock->rowCount()->willReturn(0);

        $this->hasMatch(1, 'https://php.spec/blog/blog-1')
            ->shouldBe(false);
    }

}
