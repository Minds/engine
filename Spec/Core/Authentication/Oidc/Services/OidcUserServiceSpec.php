<?php

namespace Spec\Minds\Core\Authentication\Oidc\Services;

use Minds\Core\Authentication\Oidc\Repositories\OidcUserRepository;
use Minds\Core\Authentication\Oidc\Services\OidcUserService;
use Minds\Core\Channels\Ban as ChannelBanService;
use Minds\Core\Config\Config;
use Minds\Core\Email\V2\Campaigns\Recurring\TenantUserWelcome\TenantUserWelcomeEmailer;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Queue\LegacyClient;
use Minds\Core\Security\ACL;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class OidcUserServiceSpec extends ObjectBehavior
{
    private Collaborator $oidcUserRepositoryMock;
    private Collaborator $entitiesBuilderMock;
    private Collaborator $registerQueueMock;
    private Collaborator $tenantUserWelcomeEmailer;
    private Collaborator $config;
    private Collaborator $logger;
    private Collaborator $channelBanService;

    public function let(
        OidcUserRepository $oidcUserRepositoryMock,
        EntitiesBuilder $entitiesBuilderMock,
        LegacyClient $registerQueueMock,
        TenantUserWelcomeEmailer $tenantUserWelcomeEmailer,
        Config $config,
        Logger $logger,
        ChannelBanService $channelBanService,
    ) {
        $this->beConstructedWith(
            $oidcUserRepositoryMock,
            $entitiesBuilderMock,
            ACL::_(),
            $registerQueueMock,
            $tenantUserWelcomeEmailer,
            $config,
            $logger,
            $channelBanService,
        );

        $this->oidcUserRepositoryMock = $oidcUserRepositoryMock;
        $this->entitiesBuilderMock = $entitiesBuilderMock;
        $this->registerQueueMock = $registerQueueMock;
        $this->tenantUserWelcomeEmailer = $tenantUserWelcomeEmailer;
        $this->config = $config;
        $this->logger = $logger;
        $this->channelBanService = $channelBanService;
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

    public function it_should_ban_user_from_their_sub()
    {
        $this->oidcUserRepositoryMock->getUserGuidFromSub('abc1', 1)
        ->shouldBeCalled()
        ->willReturn(123);

        $user = new User();
        $this->entitiesBuilderMock->single(123)
            ->willReturn($user);

        $this->channelBanService->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->channelBanService);
        $this->channelBanService->ban()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->suspendUserFromSub('abc1', 1)
            ->shouldBe(true);
    }

    public function it_should_return_true_if_oidc_user()
    {
        $user = new User();
        $user->set('guid', '123');

        $this->oidcUserRepositoryMock->getSubFromUserGuid('123')
            ->shouldBeCalled()
            ->willReturn((object)[]);

        $this->isOidcUser($user)->shouldBe(true);
    }

    public function it_should_return_false_if_oidc_user()
    {
        $user = new User();
        $user->set('guid', '123');

        $this->oidcUserRepositoryMock->getSubFromUserGuid('123')
            ->shouldBeCalled()
            ->willReturn(null);

        $this->isOidcUser($user)->shouldBe(false);
    }

}
