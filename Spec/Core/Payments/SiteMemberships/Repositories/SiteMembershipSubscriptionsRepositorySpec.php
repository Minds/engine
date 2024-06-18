<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\SiteMemberships\Repositories;

use DateTimeImmutable;
use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipBillingPeriodEnum;
use Minds\Core\Payments\SiteMemberships\Enums\SiteMembershipPricingModelEnum;
use Minds\Core\Payments\SiteMemberships\Repositories\DTO\SiteMembershipSubscriptionDTO;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipSubscriptionsRepository;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembership;
use Minds\Core\Payments\SiteMemberships\Types\SiteMembershipSubscription;
use Minds\Entities\User;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use ReflectionClass;
use Selective\Database\Connection;
use Selective\Database\InsertQuery;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;
use Selective\Database\UpdateQuery;
use Spec\Minds\Common\Traits\CommonMatchers;

class SiteMembershipSubscriptionsRepositorySpec extends ObjectBehavior
{
    use CommonMatchers;

    private Collaborator $mysqlHandlerMock;
    private Collaborator $mysqlClientWriterHandlerMock;
    private Collaborator $mysqlClientReaderHandlerMock;
    private Collaborator $loggerMock;
    private Collaborator $configMock;

    private ReflectionClass $siteMembershipSubscriptionDtoMock;

    public function let(
        MySQLClient $mysqlClient,
        Logger      $logger,
        Config      $config,
        Connection  $mysqlMasterConnectionHandler,
        Connection  $mysqlReaderConnectionHandler,
        PDO         $mysqlMasterConnection,
        PDO         $mysqlReaderConnection,
    ): void {
        $this->mysqlHandlerMock = $mysqlClient;

        $this->mysqlHandlerMock->getConnection(MySQLClient::CONNECTION_MASTER)
            ->willReturn($mysqlMasterConnection);
        $mysqlMasterConnectionHandler->getPdo()->willReturn($mysqlMasterConnection);
        $this->mysqlClientWriterHandlerMock = $mysqlMasterConnectionHandler;


        $this->mysqlHandlerMock->getConnection(MySQLClient::CONNECTION_REPLICA)
            ->willReturn($mysqlReaderConnection);
        $mysqlReaderConnectionHandler->getPdo()->willReturn($mysqlReaderConnection);
        $this->mysqlClientReaderHandlerMock = $mysqlReaderConnectionHandler;

        $this->loggerMock = $logger;
        $this->configMock = $config;

        $this->beConstructedThrough('buildForUnitTests', [
            $this->mysqlHandlerMock->getWrappedObject(),
            $this->configMock->getWrappedObject(),
            $this->loggerMock->getWrappedObject(),
            $this->mysqlClientWriterHandlerMock->getWrappedObject(),
            $this->mysqlClientReaderHandlerMock->getWrappedObject(),
        ]);

        $this->siteMembershipSubscriptionDtoMock = new ReflectionClass(SiteMembershipSubscriptionDTO::class);
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(SiteMembershipSubscriptionsRepository::class);
    }

    public function it_should_store_site_membership_subscriptions(
        InsertQuery  $insertQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $user = new User(123);
        $user->set('guid', 123);

        $siteMembershipSubscriptionDto = $this->generateSiteMembershipSubscriptionDtoMock(
            $user,
            new SiteMembership(1, 'name', 10, SiteMembershipBillingPeriodEnum::MONTHLY, SiteMembershipPricingModelEnum::RECURRING),
            'stripe_subscription_id',
            false,
            new DateTimeImmutable(),
            new DateTimeImmutable()
        );

        $this->configMock->get('tenant_id')->willReturn(1);

        $this->mysqlClientWriterHandlerMock->insert()
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->into('minds_site_membership_subscriptions')
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->set(Argument::that(
            fn (array $cols): bool => $cols['tenant_id'] === 1 &&
                $cols['user_guid'] === "123" &&
                $cols['membership_tier_guid'] === 1 &&
                $cols['stripe_subscription_id'] === 'stripe_subscription_id' &&
                $cols['manual'] === 0
        ))
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->onDuplicateKeyUpdate(Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->storeSiteMembershipSubscription($siteMembershipSubscriptionDto)
            ->shouldReturn(true);
    }

    public function it_should_all_site_membership_subscriptions(
        SelectQuery $selectQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $tenantId = 123;

        $this->mysqlClientReaderHandlerMock->select()
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->from('minds_site_membership_subscriptions')
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->columns([
            'id',
            'membership_tier_guid',
            'stripe_subscription_id',
            'manual',
            'auto_renew',
            'valid_from',
            'valid_to',
        ])
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where(
            'tenant_id',
            Operator::EQ,
            $tenantId
        )
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->shouldBeCalledOnce()
            ->willReturn([
                [
                    'id' => 1,
                    'membership_tier_guid' => 1,
                    'stripe_subscription_id' => 'stripe_subscription_id',
                    'auto_renew' => 1,
                    'manual' => 0,
                    'valid_from' => '2024-01-01 00:00:00',
                    'valid_to' => '2025-01-01 00:00:00',
                ]
            ]);

        $this->getAllSiteMembershipSubscriptions($tenantId)
            ->shouldYieldAnInstanceOf(SiteMembershipSubscription::class);
    }

    public function it_should_get_site_membership_subscription_by_stripe_subscription_id(
        SelectQuery $selectQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $stripeSubscriptionId = 'stripe_subscription_id';
        $tenantId = 123;

        $this->configMock->get('tenant_id')->willReturn($tenantId);

        $this->mysqlClientWriterHandlerMock->select()
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->from('minds_site_membership_subscriptions')
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->columns([
            'id',
            'membership_tier_guid',
            'stripe_subscription_id',
            'auto_renew',
            'valid_from',
            'valid_to',
        ])
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where(
            'tenant_id',
            Operator::EQ,
            new RawExp(':tenant_id')
        )
            ->shouldBeCalled()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where(
            'stripe_subscription_id',
            Operator::EQ,
            new RawExp(':stripe_subscription_id')
        )
            ->shouldBeCalled()
            ->willReturn($selectQueryMock);

        $selectQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            'tenant_id' => $tenantId,
            'stripe_subscription_id' => $stripeSubscriptionId
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $pdoStatementMock->rowCount()
            ->shouldBeCalled()
            ->willReturn(1);

        $pdoStatementMock->fetch(PDO::FETCH_ASSOC)
            ->shouldBeCalledOnce()
            ->willReturn([
                'id' => 1,
                'membership_tier_guid' => 1,
                'stripe_subscription_id' => 'stripe_subscription_id',
                'auto_renew' => 1,
                'manual' => 0,
                'valid_from' => '2024-01-01 00:00:00',
                'valid_to' => '2025-01-01 00:00:00',
            ]);

        $this->getSiteMembershipSubscriptionByStripeSubscriptionId($stripeSubscriptionId)
            ->shouldBeAnInstanceOf(SiteMembershipSubscription::class);
    }

    public function it_should_renew_membership_subscription(
        UpdateQuery $updateQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $stripeSubscriptionId = 'stripe_subscription_id';
        $startTimestamp = time();
        $endTimestamp = strtotime('+1 year', $startTimestamp);

        $this->mysqlClientWriterHandlerMock->update()
            ->shouldBeCalledOnce()
            ->willReturn($updateQueryMock);

        $updateQueryMock->table('minds_site_membership_subscriptions')
            ->shouldBeCalledOnce()
            ->willReturn($updateQueryMock);

        $updateQueryMock->set([
            'valid_from' => new RawExp(':valid_from'),
            'valid_to' => new RawExp(':valid_to')
        ])
            ->shouldBeCalledOnce()
            ->willReturn($updateQueryMock);

        $updateQueryMock->where(
            'stripe_subscription_id',
            Operator::EQ,
            new RawExp(':stripe_subscription_id')
        )
            ->shouldBeCalledOnce()
            ->willReturn($updateQueryMock);

        $updateQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
            'stripe_subscription_id' => $stripeSubscriptionId,
            'valid_from' => date('c', $startTimestamp),
            'valid_to' => date('c', $endTimestamp)
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->renewSiteMembershipSubscription(
            $stripeSubscriptionId,
            $startTimestamp,
            $endTimestamp
        )
            ->shouldBe(true);
    }

    private function generateSiteMembershipSubscriptionDtoMock(
        User|null $user,
        SiteMembership|null $siteMembership,
        string|null $stripeSubscriptionId,
        bool|null $isManual,
        DateTimeImmutable|null $validFrom,
        DateTimeImmutable|null $validTo
    ): SiteMembershipSubscriptionDTO {
        $siteMembershipSubscriptionDtoMock = $this->siteMembershipSubscriptionDtoMock->newInstanceWithoutConstructor();

        $this->siteMembershipSubscriptionDtoMock->getProperty('user')->setValue($siteMembershipSubscriptionDtoMock, $user);
        $this->siteMembershipSubscriptionDtoMock->getProperty('siteMembership')->setValue($siteMembershipSubscriptionDtoMock, $siteMembership);
        $this->siteMembershipSubscriptionDtoMock->getProperty('stripeSubscriptionId')->setValue($siteMembershipSubscriptionDtoMock, $stripeSubscriptionId);
        $this->siteMembershipSubscriptionDtoMock->getProperty('isManual')->setValue($siteMembershipSubscriptionDtoMock, $isManual);
        $this->siteMembershipSubscriptionDtoMock->getProperty('validFrom')->setValue($siteMembershipSubscriptionDtoMock, $validFrom);
        $this->siteMembershipSubscriptionDtoMock->getProperty('validTo')->setValue($siteMembershipSubscriptionDtoMock, $validTo);

        return $siteMembershipSubscriptionDtoMock;
    }
}
