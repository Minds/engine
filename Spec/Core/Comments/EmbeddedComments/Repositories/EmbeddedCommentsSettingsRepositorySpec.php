<?php

namespace Spec\Minds\Core\Comments\EmbeddedComments\Repositories;

use Minds\Core\Comments\EmbeddedComments\Models\EmbeddedCommentsSettings;
use Minds\Core\Comments\EmbeddedComments\Repositories\EmbeddedCommentsSettingsRepository;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class EmbeddedCommentsSettingsRepositorySpec extends ObjectBehavior
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
        $this->shouldHaveType(EmbeddedCommentsSettingsRepository::class);
    }

    public function it_should_return_settings(PDOStatement $pdoStatementMock)
    {
        $this->mysqlReplicaMock->prepare(Argument::any())
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            'tenant_id' => -1,
            'user_guid' => 1,
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoStatementMock->rowCount()->willReturn(1);

        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'user_guid' => 1,
                    'domain' => 'phpspec.local',
                    'path_regex' => '(.*)',
                    'auto_imports_enabled' => true
                ]
            ]);

        $settings = $this->getSettings(1);
        $settings->shouldBeAnInstanceOf(EmbeddedCommentsSettings::class);
        $settings->domain->shouldBe('phpspec.local');
        $settings->pathRegex->shouldBe('(.*)');
        $settings->autoImportsEnabled->shouldBe(true);
    }

    public function it_should_set_settings(PDOStatement $pdoStatementMock)
    {
        $settings = new EmbeddedCommentsSettings(
            userGuid: 1,
            domain: 'phpspec.local',
            pathRegex: '(.*)',
            autoImportsEnabled: true
        );
    
        $this->mysqlMasterMock->prepare(Argument::any())
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            'tenant_id' => -1,
            'user_guid' => 1,
            'domain' => 'phpspec.local',
            'path_regex' => '(.*)',
            'auto_imports_enabled' => true
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setSettings($settings)->shouldBe(true);
    }
}
