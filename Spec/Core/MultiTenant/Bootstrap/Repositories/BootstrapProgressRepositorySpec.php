<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Repositories;

use Minds\Core\MultiTenant\Bootstrap\Enums\BootstrapStepEnum;
use Minds\Core\MultiTenant\Bootstrap\Models\BootstrapStepProgress;
use PhpSpec\ObjectBehavior;
use PDO;
use PDOStatement;
use PhpSpec\Wrapper\Collaborator;
use Selective\Database\RawExp;
use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Log\Logger;
use Minds\Exceptions\ServerErrorException;
use Selective\Database\Connection;
use Selective\Database\InsertQuery;
use Selective\Database\SelectQuery;

class BootstrapProgressRepositorySpec extends ObjectBehavior
{
    private Collaborator $mysqlHandlerMock;
    private Collaborator $mysqlClientWriterHandlerMock;
    private Collaborator $mysqlClientReaderHandlerMock;
    private Collaborator $loggerMock;
    private Collaborator $configMock;

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
    }

    public function it_should_get_progress_for_tenant(PDOStatement $stmt, SelectQuery $selectQuery)
    {
        $tenantId = 1;
        $rows = [
            [
                'tenant_id' => $tenantId,
                'step_name' => 'tenant_config',
                'success' => 1,
                'last_run_timestamp' => '2023-01-01 00:00:00'
            ]
        ];

        $this->mysqlClientReaderHandlerMock->select()
            ->shouldBeCalled()
            ->willReturn($selectQuery);
        $selectQuery->from('minds_tenant_bootstrap_progress')
            ->shouldBeCalled()
            ->willReturn($selectQuery);

        $selectQuery->where('tenant_id', '=', new RawExp(':tenant_id'))
            ->shouldBeCalled()
            ->willReturn($selectQuery);

        $selectQuery->prepare()->shouldBeCalled()->willReturn($stmt);
        
        $stmt->execute(['tenant_id' => $tenantId])->shouldBeCalled();
        $stmt->fetchAll(PDO::FETCH_ASSOC)->willReturn($rows);

        $this->getProgress($tenantId)->shouldBeLike([
            new BootstrapStepProgress(
                tenantId: $tenantId,
                stepName: BootstrapStepEnum::TENANT_CONFIG_STEP,
                success: true,
                lastRunTimestamp: new \DateTime('2023-01-01 00:00:00')
            ),
            new BootstrapStepProgress(
                tenantId: $tenantId,
                stepName: BootstrapStepEnum::LOGO_STEP,
                success: false,
                lastRunTimestamp: null
            ),
            new BootstrapStepProgress(
                tenantId: $tenantId,
                stepName: BootstrapStepEnum::CONTENT_STEP,
                success: false,
                lastRunTimestamp: null
            ),
            new BootstrapStepProgress(
                tenantId: $tenantId,
                stepName: BootstrapStepEnum::FINISHED,
                success: false,
                lastRunTimestamp: null
            ),
        ]);
    }

    public function it_should_throw_exception_if_no_tenant_id_provided_and_is_not_in_config()
    {
        $this->configMock->get('tenant_id')->willReturn(null);
        $this->shouldThrow(ServerErrorException::class)->during('getProgress');
    }

    public function it_should_update_progress_for_step(PDOStatement $stmt, InsertQuery $insertQueryMock)
    {
        $tenantId = 1;
        $step = BootstrapStepEnum::CONTENT_STEP;
        $success = true;

        $this->configMock->get('tenant_id')->willReturn($tenantId);

        $this->mysqlClientWriterHandlerMock->insert()
            ->shouldBeCalled()
            ->willReturn($insertQueryMock);

        $insertQueryMock->into('minds_tenant_bootstrap_progress')
            ->shouldBeCalled()
            ->willReturn($insertQueryMock);

        $insertQueryMock->set([
            'tenant_id' => new RawExp(':tenant_id'),
            'step_name' => new RawExp(':step_name'),
            'success' => new RawExp(':success'),
            'last_run_timestamp' => new RawExp('CURRENT_TIMESTAMP'),
        ])
            ->shouldBeCalled()
            ->willReturn($insertQueryMock);

        $insertQueryMock->onDuplicateKeyUpdate([
            'success' => new RawExp(':success'),
            'last_run_timestamp' => new RawExp('CURRENT_TIMESTAMP'),
        ])
            ->shouldBeCalled()
            ->willReturn($insertQueryMock);

        $insertQueryMock->prepare()->shouldBeCalled()->willReturn($stmt);

        $stmt->execute([
            'tenant_id' => $tenantId,
            'step_name' => $step->value,
            'success' => $success ? 1 : 0,
        ])->shouldBeCalled()->willReturn(true);

        $this->updateProgress($step, $success, $tenantId)->shouldReturn(true);
    }

    public function it_should_throw_exception_if_update_fails(PDOStatement $stmt, InsertQuery $insertQueryMock)
    {
        $tenantId = 1;
        $step = BootstrapStepEnum::CONTENT_STEP;
        $success = true;

        $this->configMock->get('tenant_id')->willReturn($tenantId);

        $this->mysqlClientWriterHandlerMock->insert()
            ->shouldBeCalled()
            ->willReturn($insertQueryMock);

        $insertQueryMock->into('minds_tenant_bootstrap_progress')
            ->shouldBeCalled()
            ->willReturn($insertQueryMock);

        $insertQueryMock->set([
            'tenant_id' => new RawExp(':tenant_id'),
            'step_name' => new RawExp(':step_name'),
            'success' => new RawExp(':success'),
            'last_run_timestamp' => new RawExp('CURRENT_TIMESTAMP'),
        ])
            ->shouldBeCalled()
            ->willReturn($insertQueryMock);

        $insertQueryMock->onDuplicateKeyUpdate([
            'success' => new RawExp(':success'),
            'last_run_timestamp' => new RawExp('CURRENT_TIMESTAMP'),
        ])
            ->shouldBeCalled()
            ->willReturn($insertQueryMock);

        $insertQueryMock->prepare()->shouldBeCalled()->willReturn($stmt);

        $stmt->execute([
            'tenant_id' => $tenantId,
            'step_name' => $step->value,
            'success' => $success ? 1 : 0,
        ])->willThrow(new \PDOException());

        $this->shouldThrow(ServerErrorException::class)->during('updateProgress', [$step, $success, $tenantId]);
    }
}
