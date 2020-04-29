<?php

namespace Spec\Minds\Core\DismissibleWidgets;

use Minds\Core\DismissibleWidgets\Manager;
use Minds\Core\DismissibleWidgets\InvalidWidgetIDException;
use Minds\Entities\User;
use Minds\Core\Entities\Actions\Save;
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

    public function it_should_set_widget_id()
    {
        $this->user->getDismissedWidgets()
            ->willReturn(['existing-id']);

        $this->user->setDismissedWidgets(['existing-id', 'test-widget-id'])
            ->willReturn($this->user);

        $this->save->setEntity($this->user)
            ->shouldBeCalled()
            ->willReturn($this->save);

        $this->save->save()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setDimissedId('test-widget-id')
            ->shouldBe(true);
    }

    public function it_should_throw_exception_if_invalid_widget()
    {
        $this->shouldThrow(InvalidWidgetIDException::class)
            ->duringSetDimissedId('test-bad-widget');
    }
}
