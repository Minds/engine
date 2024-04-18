<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\Controllers;

use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\AutoLogin\AutoLoginService;
use Minds\Core\MultiTenant\Controllers\TenantsController;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\TenantsService;
use Minds\Core\MultiTenant\Services\TenantUsersService;
use Minds\Core\MultiTenant\Types\TenantLoginRedirectDetails;
use Minds\Core\MultiTenant\Types\TenantUser;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use ReflectionClass;

class TenantsControllerSpec extends ObjectBehavior
{
    private Collaborator $networksService;
    private Collaborator $usersService;
    private Collaborator $autoLoginService;
    private Collaborator $experimentsManager;
    private Collaborator $logger;

    private ReflectionClass $tenantMockFactory;
    private ReflectionClass $tenantUserMockFactory;

    public function let(
        TenantsService $networksService,
        TenantUsersService $usersService,
        AutoLoginService $autoLoginService,
        ExperimentsManager $experimentsManager,
        Logger $logger
    ) {
        $this->networksService = $networksService;
        $this->usersService = $usersService;
        $this->autoLoginService = $autoLoginService;
        $this->experimentsManager = $experimentsManager;
        $this->logger = $logger;
        $this->tenantMockFactory = new ReflectionClass(Tenant::class);
        $this->tenantUserMockFactory = new ReflectionClass(TenantUser::class);

        $this->beConstructedWith(
            $networksService,
            $usersService,
            $autoLoginService,
            $experimentsManager,
            $logger
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(TenantsController::class);
    }

    public function it_should_create_a_tenant_trial(
        User $loggedInUser,
        Tenant $tenantInput
    ) {
        $loggedInUsername = 'testuser';
        $loginUrl = 'https://example.minds.com/api/v3/multi-tenant/auto-login/login';
        $jwtToken = 'exampleJwtToken';
        $tenantId = 234;
        $rootUserGuid = 345;

        $createdTenant = $this->tenantMockFactory->newInstanceWithoutConstructor();
        $this->tenantMockFactory->getProperty('id')->setValue($createdTenant, $tenantId);

        $tenantUser = $this->tenantUserMockFactory->newInstanceWithoutConstructor();
        $this->tenantUserMockFactory->getProperty('guid')->setValue($tenantUser, $rootUserGuid);
        
        $loggedInUser->getUsername()
            ->shouldBeCalled()
            ->willReturn($loggedInUsername);

        $this->networksService->createNetworkTrial($tenantInput, $loggedInUser)
            ->shouldBeCalled()
            ->willReturn($createdTenant);

        $this->usersService->createNetworkRootUser(Argument::that(function ($tenant) use ($tenantId) {
            return true;
        }), $loggedInUser)
            ->shouldBeCalled()
            ->willReturn($tenantUser);

        $this->autoLoginService->buildLoginUrlFromTenant(
            tenant: $createdTenant
        )
            ->shouldBeCalled()
            ->willReturn($loginUrl);

        $this->autoLoginService->buildJwtTokenFromTenant(
            tenant: $createdTenant,
            loggedInUser: $loggedInUser,
            userGuid: $rootUserGuid
        )
            ->shouldBeCalled()
            ->willReturn($jwtToken);
        
        $this->tenantTrial($tenantInput, $loggedInUser)->shouldBeLike(
            new TenantLoginRedirectDetails(
                tenant: $createdTenant,
                loginUrl: $loginUrl,
                jwtToken: $jwtToken
            )
        );
    }

    public function it_should_create_a_tenant_trial_but_return_null_redirects_if_user_creation_fails(
        User $loggedInUser,
        Tenant $tenantInput
    ) {
        $loggedInUsername = 'testuser';
        $exception = new \Exception('Failed to create user');
        $tenantId = 234;

        $createdTenant = $this->tenantMockFactory->newInstanceWithoutConstructor();
        $this->tenantMockFactory->getProperty('id')->setValue($createdTenant, $tenantId);
        
        $loggedInUser->getUsername()
            ->shouldBeCalled()
            ->willReturn($loggedInUsername);

        $this->networksService->createNetworkTrial($tenantInput, $loggedInUser)
            ->shouldBeCalled()
            ->willReturn($createdTenant);

        $this->usersService->createNetworkRootUser(Argument::that(function ($tenant) use ($tenantId) {
            return true;
        }), $loggedInUser)
            ->shouldBeCalled()
            ->willThrow($exception);

        $this->autoLoginService->buildLoginUrl(
            Argument::any()
        )
            ->shouldNotBeCalled();

        $this->autoLoginService->buildJwtToken(
            Argument::any(),
            Argument::any()
        )
            ->shouldNotBeCalled();
        
        $this->logger->error($exception)
            ->shouldBeCalled();

        $this->tenantTrial($tenantInput, $loggedInUser)->shouldBeLike(
            new TenantLoginRedirectDetails(
                tenant: $createdTenant
            )
        );
    }
}
