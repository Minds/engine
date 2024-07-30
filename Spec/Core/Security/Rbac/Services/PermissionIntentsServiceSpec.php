<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Security\Rbac\Services;

use Minds\Core\Config\Config;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Core\Security\Rbac\Enums\PermissionIntentTypeEnum;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Helpers\PermissionIntentHelpers;
use Minds\Core\Security\Rbac\Models\PermissionIntent;
use Minds\Core\Security\Rbac\Repositories\PermissionIntentsRepository;
use Minds\Core\Security\Rbac\Services\PermissionIntentsService;
use PDOException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class PermissionIntentsServiceSpec extends ObjectBehavior
{
    private Collaborator $repositoryMock;
    private Collaborator $permissionIntentHelpersMock;
    private Collaborator $cacheMock;
    private Collaborator $configMock;
    private Collaborator $loggerMock;

    public function let(
        PermissionIntentsRepository $repositoryMock,
        PermissionIntentHelpers $permissionIntentHelpersMock,
        PsrWrapper $cacheMock,
        Config $configMock,
        Logger $loggerMock
    ) {
        $this->beConstructedWith(
            $repositoryMock,
            $permissionIntentHelpersMock,
            $cacheMock,
            $configMock,
            $loggerMock
        );
        $this->repositoryMock = $repositoryMock;
        $this->permissionIntentHelpersMock = $permissionIntentHelpersMock;
        $this->cacheMock = $cacheMock;
        $this->configMock = $configMock;
        $this->loggerMock = $loggerMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(PermissionIntentsService::class);
    }

    // getPermissionIntents

    public function it_should_get_permission_intents_for_tenant_from_cache(): void
    {
        $permissionIntent1 = new PermissionIntent(
            permissionId: PermissionsEnum::CAN_CREATE_POST,
            intentType: PermissionIntentTypeEnum::UPGRADE,
            membershipGuid: 123
        );
        $permissionIntent2 = new PermissionIntent(
            permissionId: PermissionsEnum::CAN_COMMENT,
            intentType: PermissionIntentTypeEnum::HIDE,
            membershipGuid: null
        );
        $permissionIntents = [ $permissionIntent1, $permissionIntent2 ];

        $this->configMock->get('tenant_id')->willReturn(1);
        $this->cacheMock->get(PermissionIntentsService::CACHE_KEY)->willReturn(serialize($permissionIntents));

        $this->repositoryMock->getPermissionIntents()
            ->shouldNotBeCalled();

        $this->getPermissionIntents()->shouldBeLike($permissionIntents);
    }

    public function it_should_get_permission_intents_for_tenant_from_db_when_not_in_cache_cache(): void
    {
        $permissionIntent1 = new PermissionIntent(
            permissionId: PermissionsEnum::CAN_CREATE_POST,
            intentType: PermissionIntentTypeEnum::UPGRADE,
            membershipGuid: 123
        );
        $permissionIntent2 = new PermissionIntent(
            permissionId: PermissionsEnum::CAN_COMMENT,
            intentType: PermissionIntentTypeEnum::HIDE,
            membershipGuid: null
        );
        $permissionIntents = [ $permissionIntent1, $permissionIntent2 ];

        $this->configMock->get('tenant_id')->willReturn(1);
        $this->cacheMock->get(PermissionIntentsService::CACHE_KEY)->willReturn(null);

        $this->repositoryMock->getPermissionIntents()
            ->shouldBeCalled()
            ->willYield($permissionIntents);

        $this->cacheMock->set(PermissionIntentsService::CACHE_KEY, serialize($permissionIntents))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->getPermissionIntents()->shouldBeLike($permissionIntents);
    }

    public function it_should_get_permission_intents_for_tenant_and_use_fallback_on_db_exception(): void
    {
        $permissionIntent1 = new PermissionIntent(
            permissionId: PermissionsEnum::CAN_CREATE_POST,
            intentType: PermissionIntentTypeEnum::UPGRADE,
            membershipGuid: 123
        );
        $permissionIntent2 = new PermissionIntent(
            permissionId: PermissionsEnum::CAN_COMMENT,
            intentType: PermissionIntentTypeEnum::HIDE,
            membershipGuid: null
        );
        $permissionIntents = [ $permissionIntent1, $permissionIntent2 ];

        $this->configMock->get('tenant_id')->willReturn(1);
        $this->cacheMock->get(PermissionIntentsService::CACHE_KEY)->willReturn(null);

        $this->repositoryMock->getPermissionIntents()
            ->shouldBeCalled()
            ->willThrow(new PDOException());

        $this->cacheMock->set(PermissionIntentsService::CACHE_KEY, serialize($permissionIntents))
            ->shouldNotBeCalled();

        $this->permissionIntentHelpersMock->getNonTenantDefaults()
            ->shouldBeCalled()
            ->willReturn($permissionIntents);

        $this->getPermissionIntents()->shouldBeLike($permissionIntents);
    }

    public function it_should_get_permission_intents_for_non_tenant_network(): void
    {
        $this->configMock->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(null);

        $permissionIntent1 = new PermissionIntent(
            permissionId: PermissionsEnum::CAN_CREATE_POST,
            intentType: PermissionIntentTypeEnum::UPGRADE,
            membershipGuid: 123
        );
        $permissionIntent2 = new PermissionIntent(
            permissionId: PermissionsEnum::CAN_COMMENT,
            intentType: PermissionIntentTypeEnum::HIDE,
            membershipGuid: null
        );
        $permissionIntents = [ $permissionIntent1, $permissionIntent2 ];

        $this->permissionIntentHelpersMock->getNonTenantDefaults()
            ->shouldBeCalled()
            ->willReturn($permissionIntents);

        $this->getPermissionIntents()->shouldBeLike($permissionIntents);
    }

    // setPermissionIntent

    public function it_should_set_permission_intent_and_update_cache(): void
    {
        $permissionId = PermissionsEnum::CAN_CREATE_POST;
        $intentType = PermissionIntentTypeEnum::UPGRADE;
        $membershipGuid = (int) Guid::build();

        $this->configMock->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(123);
    
        $this->repositoryMock->upsert(
            permissionId: $permissionId,
            intentType: $intentType,
            membershipGuid: $membershipGuid
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $permissionIntent1 = new PermissionIntent(
            permissionId: PermissionsEnum::CAN_CREATE_POST,
            intentType: PermissionIntentTypeEnum::UPGRADE,
            membershipGuid: $membershipGuid
        );
        $permissionIntent2 = new PermissionIntent(
            permissionId: PermissionsEnum::CAN_COMMENT,
            intentType: PermissionIntentTypeEnum::HIDE,
            membershipGuid: null
        );
        $permissionIntents = [ $permissionIntent1, $permissionIntent2 ];

        $this->cacheMock->get(PermissionIntentsService::CACHE_KEY)->willReturn(serialize($permissionIntents));

        $this->cacheMock->set(PermissionIntentsService::CACHE_KEY, serialize($permissionIntents))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setPermissionIntent($permissionId, $intentType, $membershipGuid)
            ->shouldBe(true);
    }
}
