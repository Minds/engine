<?php

namespace Spec\Minds\Core\Feeds\HideEntities;

use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\HideEntities\Exceptions\TooManyHiddenException;
use Minds\Core\Feeds\HideEntities\Manager;
use Minds\Core\Feeds\HideEntities\Repository;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var EntitiesBuilder */
    protected $entitiesBuilderMock;

    /** @var Repository */
    protected $repositoryMock;

    public function let(EntitiesBuilder $entitiesBuilderMock, Repository $repositoryMock)
    {
        $this->beConstructedWith($repositoryMock, $entitiesBuilderMock);
        $this->entitiesBuilderMock = $entitiesBuilderMock;
        $this->repositoryMock = $repositoryMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_hide_entity(User $user, Activity $activity)
    {
        $this->entitiesBuilderMock->single('1234')
            ->willReturn($activity);
        $activity->getGuid()
            ->willReturn('1234');

        $user->getGuid()
            ->willReturn('1235');

        $user->isPlus()
            ->willReturn(false);

        //

        $this->repositoryMock->count('1235', Argument::any())
            ->willReturn(0);

        //

        $this->repositoryMock->add(Argument::any())->willReturn(true);

        //
    
        $this->withUser($user)->hideEntityByGuid('1234')
            ->shouldBe(true);
    }

    public function it_should_not_hide_entity_if_over_quota(User $user, Activity $activity)
    {
        $this->entitiesBuilderMock->single('1234')
            ->willReturn($activity);
        $activity->getGuid()
            ->willReturn('1234');

        $user->getGuid()
            ->willReturn('1235');

        $user->isPlus()
            ->willReturn(false);

        //

        $this->repositoryMock->count('1235', Argument::any())
            ->willReturn(5);

        //

        $this->repositoryMock->add(Argument::any())->shouldNotBeCalled();

        //
    
        $this->withUser($user)->shouldThrow(TooManyHiddenException::class)->duringHideEntityByGuid('1234');
    }

    public function it_should_skip_count_if_plus(User $user, Activity $activity)
    {
        $this->entitiesBuilderMock->single('1234')
            ->willReturn($activity);
        $activity->getGuid()
            ->willReturn('1234');

        $user->getGuid()
            ->willReturn('1235');

        $user->isPlus()
            ->willReturn(true);

        //

        $this->repositoryMock->count('1235', Argument::any())
            ->shouldNotBeCalled();

        //

        $this->repositoryMock->add(Argument::any())->willReturn(true);

        //
    
        $this->withUser($user)->hideEntityByGuid('1234')
            ->shouldBe(true);
    }
}
