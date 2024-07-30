<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Security\Rbac\Repositories;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\Client as MySQLClient;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Core\Security\Rbac\Enums\PermissionIntentTypeEnum;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Helpers\PermissionIntentHelpers;
use Minds\Core\Security\Rbac\Models\PermissionIntent;
use Minds\Core\Security\Rbac\Repositories\PermissionIntentsRepository;
use PDO;
use PDOStatement;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Selective\Database\Connection;
use Selective\Database\InsertQuery;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;
use Spec\Minds\Common\Traits\CommonMatchers;

class PermissionIntentsRepositorySpec extends ObjectBehavior
{
    use CommonMatchers;
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

        $this->beConstructedWith(
            new PermissionIntentHelpers(),
            $this->mysqlHandlerMock,
            $this->configMock,
            $this->loggerMock,
            $this->mysqlClientReaderHandlerMock,
            $this->mysqlClientWriterHandlerMock,
        );

    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(PermissionIntentsRepository::class);
    }

    // getPermissionIntents

    public function it_should_get_permission_intents_when_none_are_set_in_db(
        SelectQuery $selectQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $tenantId = 123;

        $this->mysqlClientReaderHandlerMock->select()
            ->shouldBeCalled()
            ->willReturn($selectQueryMock);

        $selectQueryMock->from(PermissionIntentsRepository::TABLE_NAME)
            ->shouldBeCalled()
            ->willReturn($selectQueryMock);

        $selectQueryMock->columns([
            'tenant_id',
            'permission_id',
            'intent_type',
            'membership_guid'
        ])->shouldBeCalled()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('tenant_id', '=', $tenantId)
            ->shouldBeCalled()
            ->willReturn($selectQueryMock);

        $selectQueryMock->execute()
            ->shouldBeCalled()
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->shouldBeCalled()
            ->willReturn([]);

        $this->getPermissionIntents($tenantId)
            ->shouldYieldLike([
                new PermissionIntent(
                    permissionId: PermissionsEnum::CAN_CREATE_POST,
                    intentType: PermissionIntentTypeEnum::HIDE,
                    membershipGuid: null
                ),
                new PermissionIntent(
                    permissionId: PermissionsEnum::CAN_INTERACT,
                    intentType: PermissionIntentTypeEnum::WARNING_MESSAGE,
                    membershipGuid: null
                ),
                new PermissionIntent(
                    permissionId: PermissionsEnum::CAN_UPLOAD_VIDEO,
                    intentType: PermissionIntentTypeEnum::WARNING_MESSAGE,
                    membershipGuid: null
                ),
                new PermissionIntent(
                    permissionId: PermissionsEnum::CAN_CREATE_CHAT_ROOM,
                    intentType: PermissionIntentTypeEnum::WARNING_MESSAGE,
                    membershipGuid: null
                ),
                new PermissionIntent(
                    permissionId: PermissionsEnum::CAN_COMMENT,
                    intentType: PermissionIntentTypeEnum::WARNING_MESSAGE,
                    membershipGuid: null
                )
            ]);
    }

    public function it_should_get_permission_intents_in_correct_order_when_they_are_set_in_the_db(
        SelectQuery $selectQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $tenantId = 123;
        $membershipGuid = (int) Guid::build();

        $this->mysqlClientReaderHandlerMock->select()
            ->shouldBeCalled()
            ->willReturn($selectQueryMock);

        $selectQueryMock->from(PermissionIntentsRepository::TABLE_NAME)
            ->shouldBeCalled()
            ->willReturn($selectQueryMock);

        $selectQueryMock->columns([
            'tenant_id',
            'permission_id',
            'intent_type',
            'membership_guid'
        ])->shouldBeCalled()
            ->willReturn($selectQueryMock);

        $selectQueryMock->where('tenant_id', '=', $tenantId)
            ->shouldBeCalled()
            ->willReturn($selectQueryMock);

        $selectQueryMock->execute()
            ->shouldBeCalled()
            ->willReturn($pdoStatementMock);

        $pdoStatementMock->fetchAll(PDO::FETCH_ASSOC)
            ->shouldBeCalled()
            ->willReturn([
                [
                    'permission_id' => PermissionsEnum::CAN_CREATE_CHAT_ROOM->name,
                    'intent_type' => PermissionIntentTypeEnum::HIDE->value,
                    'membership_guid' => null
                ],
                [
                    'permission_id' => PermissionsEnum::CAN_INTERACT->name,
                    'intent_type' => PermissionIntentTypeEnum::HIDE->value,
                    'membership_guid' => null
                ],
                [
                    'permission_id' => PermissionsEnum::CAN_UPLOAD_VIDEO->name,
                    'intent_type' => PermissionIntentTypeEnum::WARNING_MESSAGE->value,
                    'membership_guid' => null
                ],
                [
                    'permission_id' => PermissionsEnum::CAN_CREATE_POST->name,
                    'intent_type' => PermissionIntentTypeEnum::UPGRADE->value,
                    'membership_guid' => $membershipGuid
                ],
                [
                    'permission_id' => PermissionsEnum::CAN_COMMENT->name,
                    'intent_type' => PermissionIntentTypeEnum::WARNING_MESSAGE->value,
                    'membership_guid' => null
                ]
            ]);

        $this->getPermissionIntents($tenantId)
            ->shouldYieldLike([
                new PermissionIntent(
                    permissionId: PermissionsEnum::CAN_CREATE_POST,
                    intentType: PermissionIntentTypeEnum::UPGRADE,
                    membershipGuid: $membershipGuid
                ),
                new PermissionIntent(
                    permissionId: PermissionsEnum::CAN_INTERACT,
                    intentType: PermissionIntentTypeEnum::HIDE,
                    membershipGuid: null
                ),
                new PermissionIntent(
                    permissionId: PermissionsEnum::CAN_UPLOAD_VIDEO,
                    intentType: PermissionIntentTypeEnum::WARNING_MESSAGE,
                    membershipGuid: null
                ),
                new PermissionIntent(
                    permissionId: PermissionsEnum::CAN_CREATE_CHAT_ROOM,
                    intentType: PermissionIntentTypeEnum::HIDE,
                    membershipGuid: null
                ),
                new PermissionIntent(
                    permissionId: PermissionsEnum::CAN_COMMENT,
                    intentType: PermissionIntentTypeEnum::WARNING_MESSAGE,
                    membershipGuid: null
                )
            ]);
    }

    // upsert

    public function it_should_upsert_values_to_the_db_with_membership_guid(
        InsertQuery $insertQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $tenantId = 123;
        $permissionId = PermissionsEnum::CAN_CREATE_POST;
        $intentType = PermissionIntentTypeEnum::UPGRADE;
        $membershipGuid = (int) Guid::build();

        $this->mysqlClientWriterHandlerMock->insert()
            ->shouldBeCalled()
            ->willReturn($insertQueryMock);

        $insertQueryMock->into(PermissionIntentsRepository::TABLE_NAME)
            ->shouldBeCalled()
            ->willReturn($insertQueryMock);

        $insertQueryMock->set([
            'tenant_id' => new RawExp(':tenant_id'),
            'permission_id' => new RawExp(':permission_id'),
            'intent_type' => new RawExp(':intent_type'),
            'membership_guid' => new RawExp(':membership_guid')
        ])
            ->shouldBeCalled()
            ->willReturn($insertQueryMock);

        $insertQueryMock->onDuplicateKeyUpdate([
            'intent_type' => new RawExp(':intent_type'),
            'membership_guid' => new RawExp(':membership_guid')
        ])
            ->shouldBeCalled()
            ->willReturn($insertQueryMock);

        $insertQueryMock->prepare()
            ->shouldBeCalled()
            ->willReturn($pdoStatementMock);

        $this->mysqlHandlerMock->bindValuesToPreparedStatement($pdoStatementMock, [
            'tenant_id' => $tenantId,
            'permission_id' => $permissionId->name,
            'intent_type' => $intentType->value,
            'membership_guid' => $membershipGuid
        ])
            ->shouldBeCalled();

        $pdoStatementMock->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->upsert(
            $permissionId,
            $intentType,
            $membershipGuid,
            $tenantId
        )
            ->shouldBe(true);
    }

    public function it_should_upsert_values_to_the_db_without_membership_guid(
        InsertQuery $insertQueryMock,
        PDOStatement $pdoStatementMock
    ): void {
        $tenantId = 123;
        $permissionId = PermissionsEnum::CAN_CREATE_POST;
        $intentType = PermissionIntentTypeEnum::HIDE;
        $membershipGuid = null;

        $this->mysqlClientWriterHandlerMock->insert()
            ->shouldBeCalled()
            ->willReturn($insertQueryMock);

        $insertQueryMock->into(PermissionIntentsRepository::TABLE_NAME)
            ->shouldBeCalled()
            ->willReturn($insertQueryMock);

        $insertQueryMock->set([
            'tenant_id' => new RawExp(':tenant_id'),
            'permission_id' => new RawExp(':permission_id'),
            'intent_type' => new RawExp(':intent_type'),
            'membership_guid' => new RawExp(':membership_guid')
        ])
            ->shouldBeCalled()
            ->willReturn($insertQueryMock);

        $insertQueryMock->onDuplicateKeyUpdate([
            'intent_type' => new RawExp(':intent_type'),
            'membership_guid' => new RawExp(':membership_guid')
        ])
            ->shouldBeCalled()
            ->willReturn($insertQueryMock);

        $insertQueryMock->prepare()
            ->shouldBeCalled()
            ->willReturn($pdoStatementMock);

        $this->mysqlHandlerMock->bindValuesToPreparedStatement($pdoStatementMock, [
            'tenant_id' => $tenantId,
            'permission_id' => $permissionId->name,
            'intent_type' => $intentType->value,
            'membership_guid' => $membershipGuid
        ])
            ->shouldBeCalled();

        $pdoStatementMock->execute()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->upsert(
            $permissionId,
            $intentType,
            $membershipGuid,
            $tenantId
        )
            ->shouldBe(true);
    }
}
