<?php

namespace Spec\Minds\Core\MultiTenant\Configs;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Data\MySQL\MySQLConnectionEnum;
use Minds\Core\Di\Di;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme;
use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;
use Minds\Core\MultiTenant\Configs\Repository;
use Minds\Exceptions\NotFoundException;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RepositorySpec extends ObjectBehavior
{
    protected $mysqlClientMock;

    protected $mysqlMasterMock;

    protected $mysqlReplicaMock;

    public function let(
        MySQLClient $mysqlClientMock,
        Logger $loggerMock,
        PDO $mysqlMasterMock,
        PDO $mysqlReplicaMock,
    ) {
        parent::beConstructedWith($mysqlClientMock, Di::_()->get(Config::class), $loggerMock);

        $this->mysqlClientMock = $mysqlClientMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::MASTER)
            ->shouldBeCalled()
            ->willReturn($mysqlMasterMock);
        $this->mysqlMasterMock = $mysqlMasterMock;

        $this->mysqlClientMock->getConnection(MySQLConnectionEnum::REPLICA)
            ->shouldBeCalled()
            ->willReturn($mysqlReplicaMock);
        $this->mysqlReplicaMock = $mysqlReplicaMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Repository::class);
    }

    public function it_should_get_config(
        PDOStatement $statement
    ) {
        $tenantId = 1234567890123456;
        $siteName = 'Test site';
        $siteEmail = 'noreply@minds.com';
        $colorSchemeValue = MultiTenantColorScheme::DARK->value;
        $primaryColor = '#fff000';
        $federationDisabled = true;
        $nsfwEnabled = 1;
        $updatedTimestamp = date('c', time());

        $this->mysqlMasterMock->prepare(Argument::any())
            ->willReturn($statement);

        $this->mysqlClientMock->bindValuesToPreparedStatement($statement, [
            'tenant_id' => $tenantId,
        ])->shouldBeCalled();

        $statement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $statement->fetch()
            ->shouldBeCalled()
            ->willReturn([
                'site_name' => $siteName,
                'site_email' => $siteEmail,
                'color_scheme' => $colorSchemeValue,
                'primary_color' => $primaryColor,
                'federation_disabled' => $federationDisabled,
                'nsfw_enabled' => $nsfwEnabled,
                'updated_timestamp' => $updatedTimestamp,
            ]);

        $this->get($tenantId)->shouldBeLike(new MultiTenantConfig(
            siteName: $siteName,
            siteEmail: $siteEmail,
            colorScheme: MultiTenantColorScheme::tryFrom($colorSchemeValue),
            primaryColor: $primaryColor,
            federationDisabled: $federationDisabled,
            nsfwEnabled: $nsfwEnabled,
            updatedTimestamp: strtotime($updatedTimestamp)
        ));
    }

    public function it_should_throw_exception_when_unable_to_get_config(
        PDOStatement $statement
    ) {
        $tenantId = 1234567890123456;

        $this->mysqlMasterMock->prepare(Argument::any())
            ->willReturn($statement);

        $this->mysqlClientMock->bindValuesToPreparedStatement($statement, [
            'tenant_id' => $tenantId,
        ])->shouldBeCalled();

        $statement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $statement->fetch()
            ->shouldBeCalled()
            ->willReturn(null);

        $this->shouldThrow(NotFoundException::class)->duringGet($tenantId);
    }

    public function it_should_upsert_configs(
        PDOStatement $statement
    ) {
        $tenantId = 1234567890123456;
        $siteName = 'Test site';
        $colorScheme = MultiTenantColorScheme::DARK;
        $primaryColor = '#fff000';
        $communityGuidelines = 'Test community guidelines';
        $federationDisabled = true;
        $nsfwEnabled = true;

        $this->mysqlMasterMock->prepare(Argument::any())
            ->willReturn($statement);

        $this->mysqlClientMock->bindValuesToPreparedStatement($statement, [
            'tenant_id' => $tenantId,
            'site_name' => $siteName,
            'color_scheme' => $colorScheme->value,
            'primary_color' => $primaryColor,
            'community_guidelines' => $communityGuidelines,
            'federation_disabled' => $federationDisabled,
            'nsfw_enabled' => $nsfwEnabled
        ])->shouldBeCalled();

        $statement->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->upsert(
            tenantId: $tenantId,
            siteName: $siteName,
            colorScheme: $colorScheme,
            primaryColor: $primaryColor,
            communityGuidelines: $communityGuidelines,
            federationDisabled: $federationDisabled,
            nsfwEnabled: $nsfwEnabled
        )->shouldBe(true);
    }
}
