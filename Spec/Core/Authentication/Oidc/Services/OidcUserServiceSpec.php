<?php

namespace Spec\Minds\Core\Authentication\Oidc\Services;

use Minds\Core\Authentication\Oidc\Repositories\OidcUserRepository;
use Minds\Core\Authentication\Oidc\Services\OidcUserService;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\ACL;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class OidcUserServiceSpec extends ObjectBehavior
{
    private Collaborator $oidcUserRepositoryMock;
    private Collaborator $entitiesBuilderMock;

    public function let(OidcUserRepository $oidcUserRepositoryMock, EntitiesBuilder $entitiesBuilderMock)
    {
        $this->beConstructedWith($oidcUserRepositoryMock, $entitiesBuilderMock, ACL::_());

        $this->oidcUserRepositoryMock = $oidcUserRepositoryMock;
        $this->entitiesBuilderMock = $entitiesBuilderMock;
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
}
