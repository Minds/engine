<?php

namespace Spec\Minds\Core\MultiTenant\CustomPages;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use Minds\Core\MultiTenant\CustomPages\Repository;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\MultiTenant\CustomPages\Enums\CustomPageTypesEnum;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    private Collaborator $configMock;
    private Collaborator $multiTenantBootServiceMock;
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

        $this->configMock = $configMock;

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

    public function it_should_return_a_page(PDOStatement $stmtMock)
    {
        $this->mysqlReplicaMock->quote(Argument::any())->willReturn("");
        $this->mysqlReplicaMock->prepare(Argument::any())->willReturn($stmtMock);

        $stmtMock->execute([
            'page_type' => CustomPageTypesEnum::COMMUNITY_GUIDELINES->value,
        ])->willReturn(true);

        $stmtMock->rowCount()->willReturn(1);

        $stmtMock->fetch(PDO::FETCH_ASSOC)->willReturn(
            [
                'tenant_id' => 1,
                'page_type' => CustomPageTypesEnum::COMMUNITY_GUIDELINES->value,
                'content' => "i am content",
                'external_link' => null,
            ]
        );

        $response = $this->getCustomPageByType(CustomPageTypesEnum::COMMUNITY_GUIDELINES);
        $response->content->shouldBe("i am content");
        $response->pageType->shouldBe(CustomPageTypesEnum::COMMUNITY_GUIDELINES);
    }

    public function it_should_save_page(PDOStatement $stmtMock)
    {
        $this->mysqlMasterMock->quote(Argument::any())->willReturn("");
        $this->mysqlMasterMock->prepare(Argument::any())->willReturn($stmtMock);

        $this->configMock->get('tenant_id')
            ->willReturn(1);

        $stmtMock->execute([
            'tenant_id' => 1,
            'page_type' => 'community_guidlines',
            'content' => "custom content",
            'external_link' => null,
        ])->willReturn(true);

        $this->setCustomPage(CustomPageTypesEnum::COMMUNITY_GUIDELINES, "custom content", null)->shouldBe(true);
    }
}
