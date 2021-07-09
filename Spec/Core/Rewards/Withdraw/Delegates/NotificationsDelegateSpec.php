<?php

namespace Spec\Minds\Core\Rewards\Withdraw\Delegates;

use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Rewards\Withdraw\Delegates\NotificationsDelegate;
use Minds\Core\Rewards\Withdraw\Request;
use Minds\Core\Sessions\ActiveSession;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class NotificationsDelegateSpec extends ObjectBehavior
{
    /** @var ActionEventsTopic */
    protected $actionEventsTopic;

    /** @var ActiveSession */
    protected $activeSession;

    public function let(ActionEventsTopic $actionEventsTopic, ActiveSession $activeSession)
    {
        $this->beConstructedWith(null, $actionEventsTopic, $activeSession);
        $this->actionEventsTopic = $actionEventsTopic;
        $this->activeSession = $activeSession;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(NotificationsDelegate::class);
    }

    public function it_should_send_action_event_on_approve(Request $request, User $admin)
    {
        $this->activeSession->getUser()
            ->willReturn($admin);
    
        $request->getAmount()->willReturn('100');
        $request->getUserGuid()->willReturn('123');
    
        $this->actionEventsTopic->send(Argument::that(function (ActionEvent $actionEvent) {
            return $actionEvent->getAction() === ActionEvent::ACTION_TOKEN_WITHDRAW_ACCEPTED
                && $actionEvent->getEntity() instanceof Request;
        }))
            ->shouldBeCalled()
            ->willReturn(true);
    
        $this->onApprove($request);
    }

    public function it_should_send_action_event_on_reject(Request $request, User $admin)
    {
        $this->activeSession->getUser()
            ->willReturn($admin);
    
        $request->getAmount()->willReturn('100');
        $request->getUserGuid()->willReturn('123');

        $this->actionEventsTopic->send(Argument::that(function (ActionEvent $actionEvent) {
            return $actionEvent->getAction() === ActionEvent::ACTION_TOKEN_WITHDRAW_REJECTED
                && $actionEvent->getEntity() instanceof Request;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->onReject($request);
    }
}
