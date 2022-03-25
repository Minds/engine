<?php

namespace Spec\Minds\Core\DismissibleNotices;

use Minds\Core\DismissibleNotices\Manager;
use Minds\Entities\User;
use Minds\Core\Entities\Actions\Save;
use Minds\Exceptions\UserErrorException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var User */
    protected $user;

    /** @var Save */
    protected $save;

    public function let(User $user, Save $save)
    {
        $this->beConstructedWith($user, $save);
        $this->user = $user;
        $this->save = $save;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_set_notice_id()
    {
        $this->user->getDismissedNotices()
            ->willReturn([
                ['id' => 'existing-id'],
                ['id' => 'test-notice-id']
            ]);

        $this->user->setDismissedNotices(Argument::that(function ($dismissed) {
            return $dismissed[2]['id'] === 'build-your-algorithm';
        }))
          ->shouldBeCalled()
          ->willReturn(new User());

        $this->save->setEntity($this->user)
            ->shouldBeCalled()
            ->willReturn($this->save);

        $this->save->save()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setDismissed('build-your-algorithm')
            ->shouldBe(true);
    }

    public function it_should_NOT_set_notice_id_if_notice_already_present()
    {
        $this->user->getDismissedNotices()
            ->willReturn([
                ['id' => 'build-your-algorithm'],
                ['id' => 'test-notice-id']
            ]);

        $this->setDismissed('build-your-algorithm')
            ->shouldBe(false);
    }

    public function it_should_throw_exception_if_invalid_notice_id()
    {
        $this->shouldThrow(new UserErrorException("Invalid Notice ID provided"))
            ->duringSetDismissed('test-bad-widget');
    }
}
