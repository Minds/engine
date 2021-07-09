<?php

namespace Spec\Minds\Core\OAuth\Repositories;

use Minds\Core\OAuth\Repositories\IdentityRepository;
use Minds\Core\EntitiesBuilder;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class IdentityRepositorySpec extends ObjectBehavior
{
    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function let(EntitiesBuilder $entitiesBuilder)
    {
        $this->beConstructedWith($entitiesBuilder);
        $this->entitiesBuilder = $entitiesBuilder;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(IdentityRepository::class);
    }

    public function it_should_build_oauth_entity_from_identifier(User $user)
    {
        $this->entitiesBuilder->single('123')
            ->willReturn($user);
        $userEntity = $this->getUserEntityByIdentifier('123');
        $userEntity->getIdentifier()->shouldBe('123');
    }
}
