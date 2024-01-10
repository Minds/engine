<?php

namespace Spec\Minds\Core\Notifications\Push\Config;

use Minds\Core\Config\Config;
use Minds\Core\Notifications\Push\Config\PushNotificationsConfigRepository;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use Minds\Core\Notifications\Push\Config\PushNotificationConfig;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class PushNotificationsConfigRepositorySpec extends ObjectBehavior
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
        $this->shouldHaveType(PushNotificationsConfigRepository::class);
    }

    public function it_should_config_from_database(PDOStatement $pdoStatementMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            'tenant_id' => -1,
        ])->willReturn(true);

        $pdoStatementMock->rowCount()->willReturn(1);

        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)->willReturn([
            [
                'apns_team_id' => 'AAAAAAAAAA',
                'apns_key' => "-----BEGIN PRIVATE KEY-----
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAA
-----END PRIVATE KEY-----",
                'apns_key_id' => 'BBBBBBBBBB',
                'apns_topic' => 'phpspec.app'
            ]
        ]);
    
        $response = $this->get(-1);
        $response->shouldBeAnInstanceOf(PushNotificationConfig::class);
        $response->apnsTeamId->shouldBe('AAAAAAAAAA');
        $response->apnsKey->shouldBe("-----BEGIN PRIVATE KEY-----
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAA
-----END PRIVATE KEY-----");
        $response->apnsKeyId->shouldBe('BBBBBBBBBB');
        $response->apnsTopic->shouldBe('phpspec.app');
    }
}
