<?php

namespace Spec\Minds\Core\Votes;

use Minds\Core\Captcha\FriendlyCaptcha\Manager as FriendlyCaptchaManager;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Core\Security\ACL;
use Minds\Core\Votes\Counters;
use Minds\Core\Votes\Indexes;
use Minds\Core\Votes\Vote;
use Minds\Entities\Activity;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var ACL */
    protected $acl;
    /** @var Counters */
    protected $counters;
    /** @var Indexes */
    protected $indexes;
    /** @var EventsDispatcher */
    protected $dispatcher;

    private $friendlyCaptchaManager;
    private $experimentsManager;

    public function let(
        ACL $acl,
        Counters $counters,
        Indexes $indexes,
        EventsDispatcher $dispatcher,
        FriendlyCaptchaManager $friendlyCaptchaManager,
        ExperimentsManager $experimentsManager
    ) {
        $this->acl = $acl;
        $this->counters = $counters;
        $this->indexes = $indexes;
        $this->dispatcher = $dispatcher;
        $this->friendlyCaptchaManager = $friendlyCaptchaManager;
        $this->experimentsManager = $experimentsManager;

        $this->beConstructedWith($counters, $indexes, $acl, $dispatcher, $this->friendlyCaptchaManager, $this->experimentsManager);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Minds\Core\Votes\Manager');
    }

    public function it_should_cast(
        Vote $vote,
        Activity $entity,
        User $user
    ) {
        $vote->getEntity()->willReturn($entity);
        $vote->getActor()->willReturn($user);
        $vote->getDirection()->willReturn('up');

        $this->setUser($user);

        $this->experimentsManager->setUser($user)
            ->willReturn($this->experimentsManager);

        $this->experimentsManager->isOn(Argument::type("string"))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->friendlyCaptchaManager->verify(Argument::type("string"), Argument::type("string"))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->acl->interact($entity, $user, 'voteup')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->counters->update($vote)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->indexes->insert($vote)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->cast($vote, ['events' => false, 'puzzleSolution' => 'puzzle'])
            ->shouldReturn(true);
    }

    public function it_should_cast_and_send_a_vote_event(
        Vote $vote,
        Activity $entity,
        User $user
    ) {
        $vote->getEntity()->willReturn($entity);
        $vote->getActor()->willReturn($user);
        $vote->getDirection()->willReturn('up');

        $this->setUser($user);

        $this->experimentsManager->setUser($user)
            ->willReturn($this->experimentsManager);

        $this->experimentsManager->isOn(Argument::type("string"))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->friendlyCaptchaManager->verify(Argument::type("string"), Argument::type("string"))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->acl->interact($entity, $user, 'voteup')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->dispatcher->trigger('vote:action:cast', Argument::any(), ['vote' => $vote], null)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->dispatcher->trigger('vote', 'up', ['vote' => $vote, 'isFriendlyCaptchaPuzzleValid' => true])
            ->shouldBeCalled()
            ->willReturn(true);

        $this->cast($vote, ['events' => true, 'puzzleSolution' => 'puzzle'])
            ->shouldReturn(true);
    }

    public function it_should_fail_to_cast_a_vote_if_the_user_hasnt_verified_its_email_address(
        Vote $vote,
        Activity $entity,
        User $user
    ) {
        $vote->getEntity()
            ->shouldBeCalled()
            ->willReturn($entity);

        $vote->getActor()
            ->shouldBeCalled()
            ->willReturn($user);

        $vote->getDirection()
            ->shouldBeCalled()
            ->willReturn('up');

        $this->acl->interact($entity, $user, 'voteup')
            ->shouldBeCalled()
            ->willThrow(UnverifiedEmailException::class);

        $this->shouldThrow(UnverifiedEmailException::class)->during('cast', [$vote, ['events' => true]]);
    }

    public function it_should_throw_during_insert_if_cannot_interact(
        Vote $vote,
        Activity $entity,
        User $user
    ) {
        $vote->getEntity()->willReturn($entity);
        $vote->getActor()->willReturn($user);
        $vote->getDirection()->willReturn('up');

        $this->acl->interact($entity, $user, 'voteup')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->counters->update($vote)
            ->shouldNotBeCalled();

        $this->indexes->insert($vote)
            ->shouldNotBeCalled();

        $this->shouldThrow(new \Exception('Actor cannot interact with entity'))
            ->duringCast($vote, ['events' => false]);
    }

    public function it_should_cancel(
        Vote $vote,
        Activity $entity,
        User $user
    ) {
        $vote->getEntity()->willReturn($entity);
        $vote->getActor()->willReturn($user);

        $this->counters->update($vote, -1)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->indexes->remove($vote)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->cancel($vote, ['events' => false])
            ->shouldReturn(true);
    }

    public function it_should_cancel_and_send_a_vote_cancel_event(
        Vote $vote,
        Activity $entity,
        User $user
    ) {
        $vote->getEntity()->willReturn($entity);
        $vote->getActor()->willReturn($user);
        $vote->getDirection()->willReturn('up');

        $this->dispatcher->trigger('vote:action:cancel', Argument::any(), ['vote' => $vote], null)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->dispatcher->trigger('vote:cancel', Argument::any(), ['vote' => $vote], null)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->cancel($vote, ['events' => true])
            ->shouldReturn(true);
    }

    public function it_should_be_true_if_has(
        Vote $vote,
        Activity $entity,
        User $user
    ) {
        $vote->getEntity()->willReturn($entity);
        $vote->getActor()->willReturn($user);

        $this->indexes->exists($vote)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->has($vote)
            ->shouldReturn(true);
    }

    public function it_should_be_false_if_not_has(
        Vote $vote,
        Activity $entity,
        User $user
    ) {
        $vote->getEntity()->willReturn($entity);
        $vote->getActor()->willReturn($user);

        $this->indexes->exists($vote)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->has($vote)
            ->shouldReturn(false);
    }

    public function it_should_toggle_on(
        Vote $vote,
        Activity $entity,
        User $user
    ) {
        $vote->getEntity()->willReturn($entity);
        $vote->getActor()->willReturn($user);
        $vote->getDirection()->willReturn('up');

        $this->setUser($user);

        $this->experimentsManager->setUser($user)
            ->willReturn($this->experimentsManager);

        $this->experimentsManager->isOn(Argument::type("string"))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->friendlyCaptchaManager->verify(Argument::type("string"), Argument::type("string"))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->acl->interact($entity, $user, 'voteup')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->indexes->exists($vote)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->counters->update($vote)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->indexes->insert($vote)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->toggle($vote, ['events' => false, 'puzzleSolution' => 'puzzle'])
            ->shouldReturn(true);
    }

    public function it_should_toggle_off(
        Vote $vote,
        Activity $entity,
        User $user
    ) {
        $vote->getEntity()->willReturn($entity);
        $vote->getActor()->willReturn($user);

        $this->indexes->exists($vote)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->indexes->exists($vote)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->counters->update($vote, -1)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->indexes->remove($vote)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->toggle($vote, ['events' => false])
            ->shouldReturn(true);
    }
}
