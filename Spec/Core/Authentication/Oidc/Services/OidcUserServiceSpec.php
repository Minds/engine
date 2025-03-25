<?php

namespace Spec\Minds\Core\Authentication\Oidc\Services;

use Minds\Core\Authentication\Oidc\Repositories\OidcUserRepository;
use Minds\Core\Authentication\Oidc\Services\OidcUserService;
use Minds\Core\Channels\Ban as ChannelBanService;
use Minds\Core\Config\Config;
use Minds\Core\Email\V2\Campaigns\Recurring\TenantUserWelcome\TenantUserWelcomeEmailer;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Queue\LegacyClient;
use Minds\Core\Security\ACL;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Minds\Core\Sessions\CommonSessions\Manager as CommonSessions;

class OidcUserServiceSpec extends ObjectBehavior
{
    private Collaborator $oidcUserRepositoryMock;
    private Collaborator $entitiesBuilderMock;
    private Collaborator $registerQueueMock;
    private Collaborator $tenantUserWelcomeEmailer;
    private Collaborator $config;
    private Collaborator $logger;
    private Collaborator $saveActionMock;

    public function let(
        OidcUserRepository $oidcUserRepositoryMock,
        EntitiesBuilder $entitiesBuilderMock,
        LegacyClient $registerQueueMock,
        TenantUserWelcomeEmailer $tenantUserWelcomeEmailer,
        Config $config,
        Logger $logger,
        Save $saveActionMock,
        CommonSessions $commonSessionsMock,
    ) {
        $this->beConstructedWith(
            $oidcUserRepositoryMock,
            $entitiesBuilderMock,
            ACL::_(),
            $registerQueueMock,
            $tenantUserWelcomeEmailer,
            $config,
            $logger,
            $saveActionMock,
            $commonSessionsMock,
        );

        $this->oidcUserRepositoryMock = $oidcUserRepositoryMock;
        $this->entitiesBuilderMock = $entitiesBuilderMock;
        $this->registerQueueMock = $registerQueueMock;
        $this->tenantUserWelcomeEmailer = $tenantUserWelcomeEmailer;
        $this->config = $config;
        $this->logger = $logger;
        $this->saveActionMock = $saveActionMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(OidcUserService::class);
    }

    public function it_should_return_user_from_oidc_sub()
    {
        $this->oidcUserRepositoryMock->getUserGuidFromSub('my-oidc-profile-sub-field', 1)
            ->shouldBeCalled()
            ->willReturn(123);

        $this->entitiesBuilderMock->single(123)
            ->willReturn(new User());
            
        $this->getUserFromSub('my-oidc-profile-sub-field', 1)
            ->shouldBeAnInstanceOf(User::class);
    }

    public function it_should_deactivate_user_from_their_sub()
    {
        $this->oidcUserRepositoryMock->getUserGuidFromSub('abc1', 1)
        ->shouldBeCalled()
        ->willReturn(123);

        $user = new User();
        $this->entitiesBuilderMock->single(123)
            ->willReturn($user);

        $this->saveActionMock->setEntity($user)
            ->shouldBeCalled()
            ->willReturn($this->saveActionMock);
        $this->saveActionMock->withMutatedAttributes(['enabled'])
            ->shouldBeCalled()
            ->willReturn($this->saveActionMock);
        $this->saveActionMock->save()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->deactivateUserFromSub('abc1', 1)
            ->shouldBe(true);
    }
}
