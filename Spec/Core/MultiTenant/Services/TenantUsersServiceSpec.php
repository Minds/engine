<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\Services;

use Minds\Core\Authentication\Services\RegisterService;
use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Repositories\TenantUsersRepository;
use Minds\Core\Entities\Actions\Save as SaveAction;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Guid;
use Minds\Core\MultiTenant\Enums\TenantUserRoleEnum;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\MultiTenant\Services\TenantUsersService;
use Minds\Core\MultiTenant\Types\TenantUser;
use Minds\Core\Security\ACL;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use ReflectionClass;
use ReflectionException;

class TenantUsersServiceSpec extends ObjectBehavior
{
    private Collaborator $tenantUsersRepository;
    private Collaborator $saveAction;
    private Collaborator $mindsConfig;
    private Collaborator $multiTenantBootService;
    private Collaborator $acl;
    private Collaborator $entitiesBuilder;
    private Collaborator $registerService;

    private ReflectionClass $tenantMockFactory;
    private ReflectionClass $tenantUserMockFactory;

    public function let(
        TenantUsersRepository $tenantUsersRepository,
        SaveAction $saveAction,
        Config $mindsConfig,
        MultiTenantBootService $multiTenantBootService,
        ACL $acl,
        EntitiesBuilder $entitiesBuilder,
        RegisterService $registerService
    ): void {
        $this->tenantUsersRepository = $tenantUsersRepository;
        $this->saveAction = $saveAction;
        $this->mindsConfig = $mindsConfig;
        $this->multiTenantBootService = $multiTenantBootService;
        $this->acl = $acl;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->registerService = $registerService;

        $this->tenantMockFactory = new ReflectionClass(Tenant::class);
        $this->tenantUserMockFactory = new ReflectionClass(TenantUser::class);

        $this->beConstructedWith(
            $this->tenantUsersRepository,
            $this->saveAction,
            $this->mindsConfig,
            $this->multiTenantBootService,
            $this->acl,
            $this->entitiesBuilder,
            $this->registerService
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(TenantUsersService::class);
    }

    public function it_should_create_a_network_root_user(
        User $sourceUser,
        User $updatedUser
    ): void {
        $updatedNetworkUserGuid = Guid::build();
        $username = 'username';
        $password = 'password';
        $email = 'noreply@minds.com';
        $tenant = $this->generateTenantMock(1);
        $networkUser = $this->generateTenantUserMock(
            guid: $updatedNetworkUserGuid,
            username: $username,
            tenantId: 1,
            role: TenantUserRoleEnum::OWNER,
            plainPassword: $password
        );

        $sourceUser->getEmail()
            ->shouldBeCalled()
            ->willReturn($email);

        $updatedUser->getGuid()
            ->shouldBeCalled()
            ->willReturn($updatedNetworkUserGuid);

        $this->multiTenantBootService->getTenant()->willReturn($tenant);

        $this->multiTenantBootService->bootFromTenantId($networkUser->tenantId)
            ->shouldBeCalledOnce();

        $this->acl->setIgnore(true)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->registerService->register(
            username: $username,
            password: $password,
            name: $username,
            email: $email,
            validatePassword: false,
            isActivityPub: false
        )
            ->shouldBeCalled()
            ->willReturn($updatedUser);

        $updatedUser->set('admin', 'yes')
            ->shouldBeCalled();

        $this->saveAction->setEntity($updatedUser)
            ->shouldBeCalled()
            ->willReturn($this->saveAction);

        $this->saveAction->withMutatedAttributes(['admin'])
            ->shouldBeCalled()
            ->willReturn($this->saveAction);
            
        $this->saveAction->save(isUpdate: true)
            ->shouldBeCalled()
            ->willReturn($this->saveAction);
    
        $this->acl->setIgnore(false)
            ->shouldBeCalled();

        $this->multiTenantBootService->resetRootConfigs()
            ->shouldBeCalled();

        $this->createNetworkRootUser(
            $networkUser,
            $sourceUser
        );
    }

    private function generateTenantMock(
        int                    $id,
        MultiTenantConfig|null $tenantConfig = null
    ): Tenant {
        $tenant = $this->tenantMockFactory->newInstanceWithoutConstructor();
        $this->tenantMockFactory->getProperty('id')->setValue($tenant, $id);
        $this->tenantMockFactory->getProperty('config')->setValue($tenant, $tenantConfig);
        $this->tenantMockFactory->getProperty('trialStartTimestamp')->setValue($tenant, strtotime('midnight'));

        return $tenant;
    }

    private function generateTenantUserMock(
        int $guid = 1234567890123456,
        string $username = 'username',
        int $tenantId = 1,
        TenantUserRoleEnum $role = TenantUserRoleEnum::USER,
        string $plainPassword = 'password'
    ): TenantUser {
        $tenantUser = $this->tenantUserMockFactory->newInstanceWithoutConstructor();

        $this->tenantUserMockFactory->getProperty('guid')->setValue($tenantUser, $guid);
        $this->tenantUserMockFactory->getProperty('username')->setValue($tenantUser, $username);
        $this->tenantUserMockFactory->getProperty('tenantId')->setValue($tenantUser, $tenantId);
        $this->tenantUserMockFactory->getProperty('role')->setValue($tenantUser, $role);
        $this->tenantUserMockFactory->getProperty('plainPassword')->setValue($tenantUser, $plainPassword);

        return $tenantUser;
    }
}
