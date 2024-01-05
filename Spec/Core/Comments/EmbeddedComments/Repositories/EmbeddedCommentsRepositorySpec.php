<?php

namespace Spec\Minds\Core\Comments\EmbeddedComments\Repositories;

use Minds\Core\Comments\EmbeddedComments\Repositories\EmbeddedCommentsRepository;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class EmbeddedCommentsRepositorySpec extends ObjectBehavior
{
    private Collaborator $mysqlClientMock;
    private Collaborator $mysqlMasterMock;
    private Collaborator $mysqlReplicaMock;

    public function let(
        Config $configMock,
        MySQLClient $mysqlClientMock,
        PDO $mysqlMasterMock,
        PDO $mysqlReplicaMock
    ) {
        $this->beConstructedWith($mysqlClientMock, $configMock, Di::_()->get('Logger'));

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
        $this->shouldHaveType(EmbeddedCommentsRepository::class);
    }

    public function it_should_return_activity_guid(PDOStatement $pdoStatementMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            'tenant_id' => -1,
            'user_guid' => 1,
            'url' => 'https://phpspec.local/'
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatementMock->rowCount()->willReturn(1);

        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'activity_guid' => 2,
                ]
            ]);

        $this->getActivityGuidFromUrl('https://phpspec.local/', 1)->shouldBe(2);
    }

    public function it_should_pair_activity_guid_and_url(PDOStatement $pdoStatementMock)
    {
        $this->mysqlMasterMock->prepare(Argument::any())
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            'tenant_id' => -1,
            'user_guid' => 1,
            'activity_guid' => 2,
            'url' => 'https://phpspec.local/'
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->addActivityGuidWithUrl(2, 'https://phpspec.local/', 1)->shouldBe(true);
    }
}
