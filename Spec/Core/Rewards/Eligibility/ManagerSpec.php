<?php

namespace Spec\Minds\Core\Rewards\Eligibility;

use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Minds\Core\Hashtags\User\Manager as UserHashtagsManager;
use Minds\Core\Feeds\User\Manager as FeedsUserManager;
use Minds\Core\Rewards\Eligibility\Manager;

class ManagerSpec extends ObjectBehavior
{
    /** @var UserHashtagsManager */
    protected $userHashtagsManager;

    /** @var FeedsUserManager */
    protected $feedUserManager;

    public function let(
        UserHashtagsManager $userHashtagsManager,
        FeedsUserManager $feedUserManager
    ) {
        $this->userHashtagsManager = $userHashtagsManager;
        $this->feedUserManager = $feedUserManager;

        $this->beConstructedWith(
            $userHashtagsManager,
            $feedUserManager
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_this_on_setting_user(User $user)
    {
        $this->setUser($user)->shouldBe($this);
    }

    public function it_should_determine_if_user_can_register(User $user)
    {
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(true);

        $user->getAge()
            ->shouldBeCalled()
            ->willReturn(259201);
        
        $user->getName()
            ->shouldBeCalled()
            ->willReturn('123');
        
        $user->get('briefdescription')
            ->shouldBeCalled()
            ->willReturn('321');

        $this->userHashtagsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->userHashtagsManager);

        $this->userHashtagsManager->hasSetHashtags()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->feedUserManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->feedUserManager);

        $this->feedUserManager->hasMadePosts()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setUser($user);

        $this->isEligible()->shouldBe(true);
    }

    public function it_should_fail_if_user_is_not_trusted(User $user)
    {
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->setUser($user);
        $this->isEligible()->shouldBe(false);
    }

    public function it_should_fail_if_user_is_newer_than_minimum_account_age(User $user)
    {
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(true);

        $user->getAge()
            ->shouldBeCalled()
            ->willReturn(259199);
        
        $this->setUser($user);
        $this->isEligible()->shouldBe(false);
    }

    public function it_should_fail_if_user_has_no_name(User $user)
    {
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(true);

        $user->getAge()
            ->shouldBeCalled()
            ->willReturn(259201);
        
        $user->getName()
            ->shouldBeCalled()
            ->willReturn('');

        $this->setUser($user);
        $this->isEligible()->shouldBe(false);
    }

    public function it_should_fail_if_user_has_no_description(User $user)
    {
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(true);

        $user->getAge()
            ->shouldBeCalled()
            ->willReturn(259201);
        
        $user->getName()
            ->shouldBeCalled()
            ->willReturn('name');

        $user->get('briefdescription')
            ->shouldBeCalled()
            ->willReturn('');

        $this->setUser($user);
        $this->isEligible()->shouldBe(false);
    }

    public function it_should_fail_if_user_has_not_made_posts(User $user)
    {
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(true);

        $user->getAge()
            ->shouldBeCalled()
            ->willReturn(259201);
        
        $user->getName()
            ->shouldBeCalled()
            ->willReturn('name');

        $user->get('briefdescription')
            ->shouldBeCalled()
            ->willReturn('desc');

        $this->feedUserManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->feedUserManager);

        $this->feedUserManager->hasMadePosts()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->setUser($user);
        $this->isEligible()->shouldBe(false);
    }

    public function it_should_fail_if_user_has_not_set_hashtags(User $user)
    {
        $user->isTrusted()
            ->shouldBeCalled()
            ->willReturn(true);

        $user->getAge()
            ->shouldBeCalled()
            ->willReturn(259201);
        
        $user->getName()
            ->shouldBeCalled()
            ->willReturn('name');

        $user->get('briefdescription')
            ->shouldBeCalled()
            ->willReturn('desc');

        $this->feedUserManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->feedUserManager);

        $this->feedUserManager->hasMadePosts()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->userHashtagsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->userHashtagsManager);

        $this->userHashtagsManager->hasSetHashtags()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->setUser($user);
        $this->isEligible()->shouldBe(false);
    }
}
