<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Payments\Stripe\Webhooks\Repositories;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\SiteMemberships\Repositories\DTO\SiteMembershipSubscriptionDTO;
use Minds\Core\Payments\Stripe\Webhooks\Model\SubscriptionsWebhookDetails;
use Minds\Core\Payments\Stripe\Webhooks\Repositories\WebhooksConfigurationRepository;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use ReflectionClass;
use Selective\Database\Connection;
use Selective\Database\InsertQuery;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;
use Spec\Minds\Common\Traits\CommonMatchers;

class WebhooksConfigurationRepositorySpec extends ObjectBehavior
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
        $this->shouldBeAnInstanceOf(WebhooksConfigurationRepository::class);
    }

    public function it_should_store_webhook_configurations(
        InsertQuery  $insertQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $webhookId = "123";
        $webhookSecret = "secret";

        $this->configMock->get('tenant_id')->willReturn(1);

        $this->mysqlClientWriterHandlerMock->insert()
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->into('minds_payments_config')
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->set([
          'tenant_id' => 1,
          'stripe_webhook_id' => new RawExp(':stripe_webhook_id'),
          'stripe_webhook_secret' => new RawExp(':stripe_webhook_secret')
      ])
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->onDuplicateKeyUpdate([
          'stripe_webhook_id' => new RawExp(':stripe_webhook_id'),
          'stripe_webhook_secret' => new RawExp(':stripe_webhook_secret')
        ])
            ->shouldBeCalledOnce()
            ->willReturn($insertQueryMock);

        $insertQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute([
          'stripe_webhook_id' => $webhookId,
          'stripe_webhook_secret' => $webhookSecret
        ])
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->storeWebhookConfiguration($webhookId, $webhookSecret)
            ->shouldReturn(true);
    }

    public function it_should_get_webhook_configurations(
        SelectQuery  $selectQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $webhookId = "123";
        $webhookSecret = "secret";

        $this->configMock->get('tenant_id')->willReturn(1);

        $this->mysqlClientWriterHandlerMock->select()
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->from('minds_payments_config')
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->columns([
            'stripe_webhook_id',
            'stripe_webhook_secret'
        ])
            ->shouldBeCalled()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('tenant_id', Operator::EQ, 1)    
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryMock);

        $selectQueryMock->prepare()
            ->shouldBeCalledOnce()
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->execute()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $pdoStatementMock->rowCount()
            ->shouldBeCalledOnce()
            ->willReturn(1);

        $pdoStatementMock->fetch(PDO::FETCH_ASSOC)
            ->shouldBeCalledOnce()
            ->willReturn([
                'stripe_webhook_id' => $webhookId,
                'stripe_webhook_secret' => $webhookSecret
            ]);

        $this->getWebhookConfiguration($webhookId, $webhookSecret)
            ->shouldBeLike(new SubscriptionsWebhookDetails(
                stripeWebhookId: $webhookId,
                stripeWebhookSecret: $webhookSecret
            ));
    }
}
